<?php

namespace App\Domain\ValueObject;

readonly class Money
{
    private function __construct(
        private float  $amount,
        private string $currency = 'MGA'
    ) {
        if ($amount < 0) {
            throw new InvalidMoneyException("Le montant ne peut pas être négatif : {$amount}");
        }
    }

    public static function of(float $amount, string $currency = 'MGA'): self
    {
        return new self(round($amount, 2), $currency);
    }

    public function amount(): float
    {
        return $this->amount;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);
        return new self($this->amount + $other->amount, $this->currency);
    }

    public function multiply(float $factor): self
    {
        return new self($this->amount * $factor, $this->currency);
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidMoneyException(
                "Devises incompatibles : {$this->currency} vs {$other->currency}"
            );
        }
    }

    public function toArray(): array
    {
        return [
            'amount'   => $this->amount,
            'currency' => $this->currency,
        ];
    }
}
