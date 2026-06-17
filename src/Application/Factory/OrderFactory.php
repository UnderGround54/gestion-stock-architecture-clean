<?php

namespace App\Application\Factory;


use App\Domain\Model\Entity\Order;
use App\Domain\Model\Entity\OrderLine;
use App\Domain\Model\Entity\Product;
use App\Domain\Port\IdGeneratorInterface;
use App\Presentation\DTO\Request\OrderLineDTO;

final readonly class OrderFactory
{
    public function __construct(
        private IdGeneratorInterface $idGenerator,
    ) {}

    public function createOrder(string $clientId, string $customerNote = ''): Order
    {
        return new Order($this->idGenerator->generate() , $clientId, $customerNote);
    }

    public function createLine(Product $product, OrderLineDTO $dto, Order $order): OrderLine
    {
        return new OrderLine(
            id:               $this->idGenerator->generate(),
            orderId:          $order->getId(),
            productId:        $product->getId(),
            productName:      $product->getName(),
            productReference: $product->getReference(),
            quantity:         $dto->quantity,
            unitPrice:        $product->getPrice()
        );
    }
}

