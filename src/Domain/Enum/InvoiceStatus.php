<?php

namespace App\Domain\Enum;

enum InvoiceStatus: string
{
    case PENDING   = 'pending';
    case PAID      = 'paid';
    case CANCELLED = 'cancelled';
    case OVERDUE   = 'overdue';

    public function getLabel(): string
    {
        return match($this) {
            self::PENDING   => 'Pending payment',
            self::PAID      => 'Paid',
            self::CANCELLED => 'Cancelled',
            self::OVERDUE   => 'Overdue',
        };
    }
}
