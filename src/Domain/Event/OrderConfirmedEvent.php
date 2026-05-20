<?php

namespace App\Domain\Event;

readonly class OrderConfirmedEvent
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $clientId,
        public readonly string $orderNumber,
        public readonly \DateTimeImmutable $occurredAt = new \DateTimeImmutable()
    ) {}
}
