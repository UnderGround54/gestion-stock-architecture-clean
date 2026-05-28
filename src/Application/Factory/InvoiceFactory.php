<?php

namespace App\Application\Factory;


use App\Domain\Model\Entity\Invoice;
use App\Domain\Model\Entity\Order;
use App\Domain\Port\IdGeneratorInterface;

final readonly class InvoiceFactory
{
    public function __construct(
        private IdGeneratorInterface $idGenerator,
    ) {}
    public function createFromOrder(Order $order, float $taxRate = 20.0, int $dueInDays = 30): Invoice
    {
        return new Invoice(
            id:            $this->idGenerator->generate(),
            orderId:       $order->getId(),
            clientId:      $order->getClientId(),
            amountExclTax: $order->getTotalAmount(),
            taxRate:       $taxRate,
            dueInDays:     $dueInDays
        );
    }
}

