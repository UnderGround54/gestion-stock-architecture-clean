<?php

namespace App\Domain\Entity;

use App\Domain\Exception\ClientInvalidException;
use Symfony\Component\Uid\Uuid;

class Client
{
    private string $id;
    private string $firstName;
    private string $lastName;
    private string $email;
    private string $phone;
    private string $address;
    private bool $isActive;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;

    /** @var Order[] */
    private array $orders = [];

    public function __construct(
        string $lastName,
        string $firstName,
        string $email,
        string $phone,
        string $address
    ) {
        $this->validate($lastName, $firstName, $email);

        $this->id        = Uuid::v4()->toRfc4122();
        $this->lastName  = $lastName;
        $this->firstName = $firstName;
        $this->email     = $email;
        $this->phone     = $phone;
        $this->address   = $address;
        $this->isActive  = true;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    // --- Règles de gestion ---

    public function getFullName(): string
    {
        return "{$this->firstName} {$this->lastName}";
    }

    public function update(string $lastName, string $firstName, string $phone, string $address): void
    {
        $this->validate($lastName, $firstName, $this->email);
        $this->lastName  = $lastName;
        $this->firstName = $firstName;
        $this->phone     = $phone;
        $this->address   = $address;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function disable(): void
    {
        $this->isActive  = false;
        $this->updatedAt = new \DateTimeImmutable();
    }

    private function validate(string $lastName, string $firstName, string $email): void
    {
        if (empty(trim($lastName))) {
            throw new ClientInvalidException("Le nom du client est obligatoire.");
        }
        if (empty(trim($firstName))) {
            throw new ClientInvalidException("Le prénom du client est obligatoire.");
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ClientInvalidException("L'email '{$email}' est invalide.");
        }
    }

    // --- Getters ---

    public function getId(): string             { return $this->id; }
    public function getLastName(): string       { return $this->lastName; }
    public function getFirstName(): string      { return $this->firstName; }
    public function getEmail(): string          { return $this->email; }
    public function getPhone(): string          { return $this->phone; }
    public function getAddress(): string        { return $this->address; }
    public function isActive(): bool            { return $this->isActive; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
}
