<?php

namespace App\Domain\Service;

use App\Domain\Exception\InvalidProductException;
use App\Domain\Model\Entity\Product;

class StockService
{
    public function decreaseStock(Product $product, int $quantity): void
    {
        if (!$product->isAvailable()) {
            throw new InvalidProductException(
                "Le produit '{$product->getName()}' n'est pas disponible."
            );
        }

        $product->decreaseStock($quantity);
    }
}
