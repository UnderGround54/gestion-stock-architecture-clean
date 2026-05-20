<?php

namespace App\Domain\Entity;

use App\Domain\Enum\InvoiceStatus;
use App\Domain\Exception\InvoiceException;
use App\Domain\ValueObject\Money;
use Symfony\Component\Uid\Uuid;

class Invoice
{
    private string $id;
    private string $number;
    private string $orderId;
    private string $clientId;
    private InvoiceStatus $status;
    private Money $amountExclTax;
    private float $taxRate;
    private Money $taxAmount;
    private Money $amountInclTax;
    private \DateTimeImmutable $dueDate;
    private ?\DateTimeImmutable $paidAt;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        string $orderId,
        string $clientId,
        Money $amountExclTax,
        float $taxRate = 20.0,
        int $dueInDays = 30
    ) {
        $this->id            = Uuid::v4()->toRfc4122();
        $this->number        = $this->generateNumber();
        $this->orderId       = $orderId;
        $this->clientId      = $clientId;
        $this->status        = InvoiceStatus::PENDING;
        $this->amountExclTax = $amountExclTax;
        $this->taxRate       = $taxRate;
        $this->taxAmount     = $amountExclTax->multiply($taxRate / 100);
        $this->amountInclTax = $amountExclTax->add($this->taxAmount);
        $this->dueDate       = new \DateTimeImmutable("+{$dueInDays} days");
        $this->paidAt        = null;
        $this->createdAt     = new \DateTimeImmutable();
        $this->updatedAt     = new \DateTimeImmutable();
    }

    // --- Règles de gestion ---

    public function markAsPaid(): void
    {
        if ($this->status === InvoiceStatus::PAID) {
            throw new InvoiceException("La facture {$this->number} est déjà payée.");
        }

        if ($this->status === InvoiceStatus::CANCELLED) {
            throw new InvoiceException("Impossible de payer une facture annulée.");
        }

        $this->status    = InvoiceStatus::PAID;
        $this->paidAt    = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function cancel(): void
    {
        if ($this->status === InvoiceStatus::PAID) {
            throw new InvoiceException("Impossible d'annuler une facture déjà payée.");
        }

        $this->status    = InvoiceStatus::CANCELLED;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function checkOverdue(): void
    {
        if (
            $this->status === InvoiceStatus::PENDING
            && new \DateTimeImmutable() > $this->dueDate
        ) {
            $this->status    = InvoiceStatus::OVERDUE;
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function isOverdue(): bool
    {
        return $this->status === InvoiceStatus::OVERDUE
            || (
                $this->status === InvoiceStatus::PENDING
                && new \DateTimeImmutable() > $this->dueDate
            );
    }

    private function generateNumber(): string
    {
        return 'FAC-' . date('Ymd') . '-' . strtoupper(substr(Uuid::v4()->toRfc4122(), 0, 8));
    }

    // --- Getters ---

    public function getId(): string                { return $this->id; }
    public function getNumber(): string            { return $this->number; }
    public function getOrderId(): string           { return $this->orderId; }
    public function getClientId(): string          { return $this->clientId; }
    public function getStatus(): InvoiceStatus     { return $this->status; }
    public function getAmountExclTax(): Money      { return $this->amountExclTax; }
    public function getTaxRate(): float            { return $this->taxRate; }
    public function getTaxAmount(): Money          { return $this->taxAmount; }
    public function getAmountInclTax(): Money      { return $this->amountInclTax; }
    public function getDueDate(): \DateTimeImmutable   { return $this->dueDate; }
    public function getPaidAt(): ?\DateTimeImmutable   { return $this->paidAt; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
}
