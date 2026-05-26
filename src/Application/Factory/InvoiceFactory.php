<?php

namespace App\Application\Factory;


use App\Domain\Model\Entity\Invoice;
use App\Domain\Model\Entity\Order;

final class InvoiceFactory
{
    public function createFromOrder(Order $order, float $taxRate = 20.0, int $dueInDays = 30): Invoice
    {
        return new Invoice(
            orderId:       $order->getId(),
            clientId:      $order->getClientId(),
            amountExclTax: $order->getTotalAmount(),
            taxRate:       $taxRate,
            dueInDays:     $dueInDays
        );
    }
}

