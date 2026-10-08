<?php

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Model\Entity\Product;
use App\Domain\Model\Repository\ProductRepositoryInterface;
use App\Domain\ValueObject\Money;
use App\Infrastructure\Hydration\ReflectionHydrator;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;

final readonly class ProductDoctrineRepository extends AbstractDoctrineRepository implements ProductRepositoryInterface
{
    public function __construct(
        Connection $connection,
        private ReflectionHydrator $hydrator
    ) {
        parent::__construct($connection);
    }

    /**
     * @throws Exception
     */
    public function save(Product $product): void
    {
        $exists = (bool) $this->connection->fetchOne('SELECT id FROM products WHERE id = ?', [$product->getId()]);
        $data = [
            'id'          => $product->getId(),
            'name'        => $product->getName(),
            'reference'   => $product->getReference(),
            'description' => $product->getDescription(),
            'stock_quantity' => $product->getStockQuantity(),
            'minimum_stock' => $product->getMinimumStock(),
            'active'      => $product->isActive() ? 1 : 0,
            'updated_at'  => $product->getUpdatedAt()->format('Y-m-d H:i:s'),
            'price_amount' => $product->getPrice()->amount(),
            'price_currency' => $product->getPrice()->currency(),
        ];

        if (!$exists) {
            $data['created_at'] = $product->getCreatedAt()->format('Y-m-d H:i:s');
        }

        $this->upsert('products', $data, $exists);
    }

    /**
     * @throws Exception
     */
    public function findById(string $id): ?Product
    {
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM products WHERE id = ?',
            [$id]
        );

        return $row ? $this->hydrate($row) : null;
    }

    /**
     * @throws Exception
     */
    public function findByReference(string $reference): ?Product
    {
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM products WHERE reference = ?',
            [$reference]
        );

        return $row ? $this->hydrate($row) : null;
    }

    /**
     * Loads all products with advanced pagination, sorting and filtering.
     *
     * @throws Exception
     */
    public function findAll(int $page, int $limit, ?array $sort = null, array $filters = []): array
    {
        $offset = ($page - 1) * $limit;

        // Construire la requête WHERE dynamique basée sur les filtres
        $whereConditions = [];
        $parameters = [];

        foreach ($filters as $field => $value) {
            // Pour l'instant, on supporte seulement l'égalité exacte
            $whereConditions[] = "$field = ?";
            $parameters[] = $value;
        }

        $whereClause = '';
        if (!empty($whereConditions)) {
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        }

        // Déterminer l'ordre de tri
        $orderByClause = 'ORDER BY created_at DESC'; // Défaut
        if ($sort !== null && count($sort) === 2) {
            $field = $sort[0];
            $direction = strtoupper($sort[1]);

            // Valider que la direction est soit ASC soit DESC
            if ($direction === 'ASC' || $direction === 'DESC') {
                // Pour l'instant, on autorise seulement certains champs pour des raisons de sécurité
                $allowedFields = ['id', 'name', 'reference', 'stock_quantity', 'minimum_stock', 'active', 'created_at', 'updated_at'];
                if (in_array($field, $allowedFields)) {
                    $orderByClause = "ORDER BY $field $direction";
                }
            }
        }

        $sql = "SELECT * FROM products $whereClause $orderByClause LIMIT ? OFFSET ?";
        $parameters[] = $limit;
        $parameters[] = $offset;

        // Déterminer les types de paramètres
        $types = array_fill(0, count($parameters) - 2, ParameterType::STRING); // Pour les filtres
        $types[] = ParameterType::INTEGER; // LIMIT
        $types[] = ParameterType::INTEGER; // OFFSET

        $rows = $this->connection->fetchAllAssociative($sql, $parameters, $types);

        return array_map(fn($row) => $this->hydrate($row), $rows);
    }

    /**
     * Count all products with optional filtering.
     *
     * @throws Exception
     */
    public function countAll(array $filters = []): int
    {
        // Construire la requête WHERE dynamique basée sur les filtres
        $whereConditions = [];
        $parameters = [];

        foreach ($filters as $field => $value) {
            // Pour l'instant, on supporte seulement l'égalité exacte
            $whereConditions[] = "$field = ?";
            $parameters[] = $value;
        }

        $whereClause = '';
        if (!empty($whereConditions)) {
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        }

        $sql = "SELECT COUNT(*) FROM products $whereClause";

        // Déterminer les types de paramètres (tous des chaînes pour l'instant)
        $types = array_fill(0, count($parameters), ParameterType::STRING);

        return (int) $this->connection->fetchOne($sql, $parameters, $types);
    }

    /**
     * @throws Exception
     */
    public function findActive(int $page, int $limit): array
    {
        $offset = ($page - 1) * $limit;
        $rows   = $this->connection->fetchAllAssociative(
            'SELECT * FROM products WHERE active = 1 ORDER BY created_at DESC LIMIT ? OFFSET ?',
            [$limit, $offset],
            [ParameterType::INTEGER, ParameterType::INTEGER]
        );

        return array_map(fn($row) => $this->hydrate($row), $rows);
    }

    /**
     * @throws Exception
     */
    public function existsByReference(string $reference, ?string $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM products WHERE reference = ?';
        $params = [$reference];

        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }

        return (int) $this->connection->fetchOne($sql, $params) > 0;
    }

    /**
     * @throws Exception
     */
    public function delete(Product $product): void
    {
        $this->connection->delete('products', ['id' => $product->getId()]);
    }

    /**
     * @throws \Exception
     */
    private function hydrate(array $row): Product
    {
        $product = new Product(
            id:            $row['id'],
            name:          $row['name'],
            reference:     $row['reference'],
            description:   $row['description'],
            price:         Money::of((float) $row['price_amount'], $row['price_currency']),
            stockQuantity: (int) $row['stock_quantity'],
            minimumStock:  (int) $row['minimum_stock']
        );

        $this->hydrator->setMany($product, [
            'id'        => $row['id'],
            'createdAt' => new \DateTimeImmutable($row['created_at']),
            'updatedAt' => new \DateTimeImmutable($row['updated_at']),
        ]);

        if (!(bool) $row['active']) {
            $this->hydrator->set($product, 'active', false);
        }

        return $product;
    }
}
