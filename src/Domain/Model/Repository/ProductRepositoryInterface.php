<?php

namespace App\Domain\Model\Repository;

use App\Domain\Model\Entity\Product;

interface ProductRepositoryInterface
{
    public function save(Product $product): void;
    public function findById(string $id): ?Product;
    public function findByReference(string $reference): ?Product;
    public function findAll(int $page, int $limit, ?array $sort = null, array $filters = []): array;
    public function countAll(array $filters = []): int;
    public function findActive(int $page, int $limit): array;
    public function existsByReference(string $reference, ?string $excludeId = null): bool;
    public function delete(Product $product): void;
}