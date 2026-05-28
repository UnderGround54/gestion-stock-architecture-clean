# API Gestion Stock & Orders — Symfony 7.4 Clean Architecture

## Architecture

```
src/
├── Domain/                          ← Cœur métier pur (0 dépendance externe)
│   ├── Entity/                      ← Product, Client, Order, OrderLine, Invoice
│   ├── ValueObject/                 ← Money
│   ├── Enum/                        ← StatusOrder, StatusInvoice
│   ├── Event/                       ← OrderCreatEvent, InvoiceCreatEvent...
│   ├── Exception/                   ← Exceptions métier typées
│   ├── Repository/                  ← Interfaces uniquement
│   └── Service/                     ← Services métier purs
│
├── Application/                     ← Orchestration des règles
│   ├── UseCase/                     ← Un UseCase = une action métier
│   │   ├── Product/                 ← CreatProduct, ListerProducts, MettreAJourStock
│   │   ├── Order/                   ← CreatOrder, ConfirmOrder
│   │   └── Invoice/                 ← GenerateInvoice
│   └── Factory/                     ← OrderFactory, InvoiceFactory
│
├── Infrastructure/                  ← Technique (Doctrine, Events, Mailer...)
│   ├── Persistence/Doctrine/        ← Implémentations des Repository interfaces
│   └── EventListener/               ← NotificationEventListener
│
└── Presentation/                    ← HTTP In/Out
    ├── Controller/Api/              ← ProductController, OrderController...
    ├── Response/ApiResponse.php     ← Format JSON uniforme
    ├── Transformer/                 ← Domain Entity → Array JSON
    ├── EventListener/               ← GlobalExceptionListener
    └── DTO/Request/                 ← DTOs avec validations (#[Assert\...])
```

---

## Principes SOLID appliqués

| Principe | Exemple dans le projet |
|---|---|
| **S** — Single Responsibility | Chaque UseCase a une seule responsabilité (CreatOrder ne gère pas la facturation) |
| **O** — Open/Closed | Ajouter un nouveau statut de commande = ajouter une valeur à l'Enum, pas modifier les entités |
| **L** — Liskov Substitution | `OrderRepository` est substituable à `OrderRepositoryInterface` |
| **I** — Interface Segregation | `ProductRepositoryInterface` séparée de `OrderRepositoryInterface` |
| **D** — Dependency Inversion | Les UseCases dépendent des interfaces Repository, pas de Doctrine directement |

---

## Endpoints API

### Products
```
GET    /api/v1/products              → Liste paginée
GET    /api/v1/products/{id}         → Détail
POST   /api/v1/products              → Créer
PATCH  /api/v1/products/{id}/stock   → Mettre à jour le stock
```

### Clients
```
GET    /api/v1/clients               → Liste paginée
GET    /api/v1/clients/{id}          → Détail
POST   /api/v1/clients               → Créer
DELETE /api/v1/clients/{id}          → Désactiver
```

### Orders
```
GET    /api/v1/orders             → Liste paginée
GET    /api/v1/orders/{id}        → Détail
POST   /api/v1/orders             → Créer (décrémente le stock)
PATCH  /api/v1/orders/{id}/confirm → Confirm
```

### Invoices
```
GET    /api/v1/invoices              → Liste paginée
GET    /api/v1/invoices/{id}         → Détail
POST   /api/v1/invoices/orders/{commandeId}/generate → Générer
PATCH  /api/v1/invoices/{id}/pay   → Marquer payée
```

---

## Format de réponse JSON uniforme

### Succès
```json
{
  "success": true,
  "code": 200,
  "message": "ProductOrm récupéré.",
  "data": {
    "id": "uuid",
    "nom": "Riz Vary Gasy",
    "reference": "RIZ-001",
    "prix": { "amount": 5000.00, "currency": "MGA" },
    "stock": {
      "quantite": 150,
      "minimum": 10,
      "disponible": true,
      "sous_minimum": false,
      "rupture": false
    }
  }
}
```

### Liste paginée
```json
{
  "success": true,
  "code": 200,
  "message": "Liste des products récupérée.",
  "data": [...],
  "meta": {
    "pagination": {
      "total": 42,
      "page": 1,
      "limit": 10,
      "total_pages": 5,
      "has_next": true,
      "has_prev": false
    }
  }
}
```

### Erreur de validation
```json
{
  "success": false,
  "code": 422,
  "message": "Données invalides.",
  "data": null,
  "errors": {
    "nom": ["Le nom est obligatoire."],
    "prix": ["Le prix doit être positif."]
  }
}
```

### Erreur métier
```json
{
  "success": false,
  "code": 409,
  "message": "Un client avec l'email 'jean@test.mg' existe déjà.",
  "data": null
}
```

---

## Exemples de requêtes

### Créer un produit
```bash
POST /api/v1/products
Content-Type: application/json

{
  "nom": "Riz Vary Gasy",
  "reference": "RIZ-001",
  "description": "Riz local de qualité supérieure",
  "prix": 5000,
  "quantite_stock": 200,
  "stock_minimum": 20,
  "devise": "MGA"
}
```

### Créer une commande
```bash
POST /api/v1/orders
Content-Type: application/json

{
  "client_id": "uuid-client",
  "note_client": "Livraison avant 12h",
  "lignes": [
    { "produit_id": "uuid-produit-1", "quantite": 3 },
    { "produit_id": "uuid-produit-2", "quantite": 1 }
  ]
}
```

### Générer une facture
```bash
POST /api/v1/invoices/orders/{commandeId}/generer
Content-Type: application/json

{
  "taux_tva": 20.0
}
```

---

## Flux complet : Order → Invoice

```
1. POST /api/v1/clients          → Créer le client
2. POST /api/v1/products         → Créer les products avec stock
3. POST /api/v1/orders        → Créer la commande (stock décrémenté automatiquement)
                                    → Event: OrderCreatEvent dispatché
4. PATCH /orders/{id}/confirmer → Confirm la commande
                                    → Event: OrderConfirmeeEvent dispatché
5. POST /invoices/orders/{id}/generer → Générer la facture
                                    → Event: InvoiceCreatEvent dispatché
6. PATCH /invoices/{id}/payer    → Marquer la facture payée
```

---

## Règles de gestion implementées

- **Product** : référence unique, stock ne peut être négatif, alerte si stock < minimum
- **Client** : email unique, validation format email
- **Order** : doit avoir au moins une ligne, produit doit être disponible, transitions de statut strictes (EN_ATTENTE → CONFIRMEE → EXPEDIEE → LIVREE)
- **Invoice** : générée uniquement pour orders CONFIRMEES, pas de double facturation, détection retard automatique

---
