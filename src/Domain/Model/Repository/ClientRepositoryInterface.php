<?php

namespace App\Domain\Model\Repository;

use App\Domain\Model\Entity\Client;

interface ClientRepositoryInterface
{
    public function save(Client $client): void;
    public function findById(string $id): ?Client;
    public function findByEmail(string $email): ?Client;
    public function findAll(int $page, int $limit, ?array $sort = null, array $filters = []): array;
    public function countAll(array $filters = []): int;
    public function existsByEmail(string $email, ?string $excludeId = null): bool;
    public function delete(Client $client): void;
}