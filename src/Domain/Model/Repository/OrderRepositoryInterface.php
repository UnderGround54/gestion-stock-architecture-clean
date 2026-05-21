<?php

namespace App\Domain\Model\Repository;

use App\Domain\Model\Entity\Order;

interface OrderRepositoryInterface
{
    public function save(Order $order): void;
    public function findById(string $id): ?Order;
    public function findByClientId(string $clientId, int $page, int $limit): array;
    public function countByClientId(string $clientId): int;
    public function findAll(int $page, int $limit): array;
    public function countAll(): int;
    public function delete(Order $order): void;
}
