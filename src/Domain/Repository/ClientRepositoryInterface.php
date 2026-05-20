<?php

namespace App\Domain\Repository;

use App\Domain\Entity\Client;

interface ClientRepositoryInterface
{

    public function save(Client $client): void;
    public function findById(string $id): ?Client;
    public function findByEmail(string $email): ?Client;
    public function findAll(int $page, int $limit): array;
    public function countAll(): int;
    public function existsByEmail(string $email, ?string $excludeId = null): bool;
    public function delete(Client $client): void;


}
