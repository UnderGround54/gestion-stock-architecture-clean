<?php

namespace App\Domain\Enum;

enum StockOperation: string
{
    case INCREASE = 'increase';
    case DECREASE = 'decrease';

    public function label(): string
    {
        return match($this) {
            self::INCREASE => 'Augmenter',
            self::DECREASE => 'Diminuer'
        };
    }
}
