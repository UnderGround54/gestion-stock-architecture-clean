<?php

namespace App\Domain\Enum;

enum OrderStatus: string
{
    case PENDING   = 'pending';
    case CONFIRMED = 'confirmed';
    case SHIPPED   = 'shipped';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::PENDING   => 'Pending',
            self::CONFIRMED => 'Confirmed',
            self::SHIPPED   => 'Shipped',
            self::DELIVERED => 'Delivered',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function canBeCancelled(): bool
    {
        return in_array($this, [self::PENDING, self::CONFIRMED], true);
    }

    public function canBeConfirmed(): bool
    {
        return $this === self::PENDING;
    }
}
