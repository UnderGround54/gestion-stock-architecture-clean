<?php

namespace App\Domain\Event;

readonly class InsufficientStockEvent
{
    public function __construct(
        public readonly string $productId,
        public readonly string $productName,
        public readonly int $currentQuantity,
        public readonly int $minimumStock,
        public readonly \DateTimeImmutable $occurredAt = new \DateTimeImmutable()
    ) {}
}
