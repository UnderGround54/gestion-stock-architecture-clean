<?php

namespace App\Domain\Model\Entity;

use App\Domain\Enum\OrderStatus;
use App\Domain\Exception\OrderException;
use App\Domain\ValueObject\Money;

class Order
{
    private string $id;
    private string $number;
    private string $clientId;
    private OrderStatus $status;
    private Money $totalAmount;
    private string $customerNote;

    /** @var OrderLine[] */
    private array $orderLines = [];

    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;

    public function __construct(string $id, string $clientId, string $customerNote = '')
    {
        $this->id = $id;
        $this->number = $this->generateNumber();
        $this->clientId = $clientId;
        $this->status = OrderStatus::PENDING;
        $this->totalAmount = Money::of(0);
        $this->customerNote = $customerNote;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    // --- Règles de gestion ---

    public function addLine(OrderLine $line): void
    {
        if ($this->status !== OrderStatus::PENDING) {
            throw new OrderException(
                "Impossible d'ajouter une ligne à une commande en statut : {$this->status->label()}"
            );
        }

        $this->orderLines[] = $line;
        $this->recalculateTotal();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function confirm(): void
    {
        if (!$this->status->canBeConfirmed()) {
            throw new OrderException(
                "La commande ne peut pas être confirmée depuis le statut : {$this->status->label()}"
            );
        }

        if (empty($this->orderLines)) {
            throw new OrderException("Impossible de confirmer une commande sans lignes.");
        }

        $this->status = OrderStatus::CONFIRMED;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function ship(): void
    {
        if ($this->status !== OrderStatus::CONFIRMED) {
            throw new OrderException(
                "La commande doit être confirmée avant d'être expédiée."
            );
        }

        $this->status = OrderStatus::SHIPPED;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function deliver(): void
    {
        if ($this->status !== OrderStatus::SHIPPED) {
            throw new OrderException("La commande doit être expédiée avant d'être livrée.");
        }

        $this->status = OrderStatus::DELIVERED;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function cancel(): void
    {
        if (!$this->status->canBeCancelled()) {
            throw new OrderException(
                "La commande ne peut pas être annulée depuis le statut : {$this->status->label()}"
            );
        }

        $this->status = OrderStatus::CANCELLED;
        $this->updatedAt = new \DateTimeImmutable();
    }

    private function recalculateTotal(): void
    {
        $total = Money::of(0);
        foreach ($this->orderLines as $line) {
            $total = $total->add($line->getSubTotal());
        }
        $this->totalAmount = $total;
    }

    private function generateNumber(): string
    {
        return 'CMD-' . date('Ymd') . '-' . strtoupper(substr($this->id, 0, 8));
    }

    // --- Getters ---

    public function getId(): string
    {
        return $this->id;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    public function getTotalAmount(): Money
    {
        return $this->totalAmount;
    }

    public function getCustomerNote(): string
    {
        return $this->customerNote;
    }

    public function getOrderLines(): array
    {
        return $this->orderLines;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
