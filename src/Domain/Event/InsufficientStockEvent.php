<?php

namespace App\Domain\Event;

readonly class InsufficientStockEvent
{
    public function __construct(
        public string $productId,
        public string $productName,
        public int $currentQuantity,
        public int $minimumStock,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable()
    ) {}
}
