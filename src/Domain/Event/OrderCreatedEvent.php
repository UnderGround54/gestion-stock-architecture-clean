<?php

namespace App\Domain\Event;

readonly class OrderCreatedEvent
{
    public function __construct(
        public string             $orderId,
        public string             $clientId,
        public string             $orderNumber,
        public float              $totalAmount,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable()
    ) {}
}
