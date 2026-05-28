<?php

namespace App\Domain\Model\Entity;

use App\Domain\ValueObject\Money;

class OrderLine
{
    private string $id;
    private string $productId;
    private string $productName;
    private string $productReference;
    private int $quantity;
    private Money $unitPrice;
    private Money $subTotal;

    public function __construct(
        string $id,
        string $productId,
        string $productName,
        string $productReference,
        int    $quantity,
        Money  $unitPrice
    )
    {
        $this->id = $id;
        $this->productId = $productId;
        $this->productName = $productName;
        $this->productReference = $productReference;
        $this->quantity = $quantity;
        $this->unitPrice = $unitPrice;
        $this->subTotal = $unitPrice->multiply($quantity);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function getProductReference(): string
    {
        return $this->productReference;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getUnitPrice(): Money
    {
        return $this->unitPrice;
    }

    public function getSubTotal(): Money
    {
        return $this->subTotal;
    }
}
