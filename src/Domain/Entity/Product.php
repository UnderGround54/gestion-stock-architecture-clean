<?php

namespace App\Domain\Entity;

use App\Domain\Exception\InsufficientStockException;
use App\Domain\Exception\InvalidProductException;
use App\Domain\ValueObject\Money;
use Symfony\Component\Uid\Uuid;

class Product
{
    private string $id;
    private string $name;
    private string $reference;
    private string $description;
    private Money $price;
    private int $stockQuantity;
    private int $minimumStock;
    private bool $active;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        string $name,
        string $reference,
        string $description,
        Money $price,
        int $stockQuantity,
        int $minimumStock = 5
    ) {
        $this->validate($name, $reference, $stockQuantity);

        $this->id            = Uuid::v4()->toRfc4122();
        $this->name          = $name;
        $this->reference     = $reference;
        $this->description   = $description;
        $this->price         = $price;
        $this->stockQuantity = $stockQuantity;
        $this->minimumStock  = $minimumStock;
        $this->active        = true;
        $this->createdAt     = new \DateTimeImmutable();
        $this->updatedAt     = new \DateTimeImmutable();
    }

    // --- Règles de gestion (Business Rules) ---

    public function decreaseStock(int $quantity): void
    {
        if ($quantity <= 0) {
            throw new InvalidProductException("La quantité doit être positive.");
        }

        if ($this->stockQuantity < $quantity) {
            throw new InsufficientStockException(
                "Stock insuffisant pour le produit '{$this->name}'. "
                . "Disponible: {$this->stockQuantity}, Demandé: {$quantity}"
            );
        }

        $this->stockQuantity -= $quantity;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function increaseStock(int $quantity): void
    {
        if ($quantity <= 0) {
            throw new InvalidProductException("La quantité doit être positive.");
        }

        $this->stockQuantity += $quantity;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function isOutOfStock(): bool
    {
        return $this->stockQuantity === 0;
    }

    public function isBelowMinimumStock(): bool
    {
        return $this->stockQuantity < $this->minimumStock;
    }

    public function isAvailable(): bool
    {
        return $this->active && !$this->isOutOfStock();
    }

    public function deactivate(): void
    {
        $this->active    = false;
        $this->updatedAt = new \DateTimeImmutable();
    }

    // --- Validation ---

    private function validate(string $name, string $reference, int $quantity): void
    {
        if (empty(trim($name))) {
            throw new InvalidProductException("Le nom du produit est obligatoire.");
        }

        if (empty(trim($reference))) {
            throw new InvalidProductException("La référence du produit est obligatoire.");
        }

        if ($quantity < 0) {
            throw new InvalidProductException("La quantité initiale ne peut pas être négative.");
        }
    }

    // --- Getters ---

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getPrice(): Money
    {
        return $this->price;
    }

    public function getStockQuantity(): int
    {
        return $this->stockQuantity;
    }

    public function getMinimumStock(): int
    {
        return $this->minimumStock;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
