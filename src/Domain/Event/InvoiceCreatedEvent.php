<?php

namespace App\Domain\Event;

readonly class InvoiceCreatedEvent
{
    public function __construct(
        public readonly string $invoiceId,
        public readonly string $orderId,
        public readonly string $clientId,
        public readonly string $invoiceNumber,
        public readonly float $totalAmountInclTax,
        public readonly \DateTimeImmutable $occurredAt = new \DateTimeImmutable()
    ) {}
}
