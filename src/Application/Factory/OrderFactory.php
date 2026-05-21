<?php

namespace App\Application\Factory;


use App\Application\DTO\Request\OrderLineDTO;
use App\Domain\Model\Entity\Order;
use App\Domain\Model\Entity\OrderLine;
use App\Domain\Model\Entity\Product;

final class OrderFactory
{
    public static function create(string $clientId, string $customerNote = ''): Order
    {
        return new Order($clientId, $customerNote);
    }

    public static function createLine(Product $product, OrderLineDTO $dto): OrderLine
    {
        return new OrderLine(
            productId:        $product->getId(),
            productName:      $product->getName(),
            productReference: $product->getReference(),
            quantity:         $dto->quantity,
            unitPrice:        $product->getPrice()
        );
    }
}

