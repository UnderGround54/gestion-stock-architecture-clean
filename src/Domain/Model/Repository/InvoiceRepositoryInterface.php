<?php

namespace App\Domain\Model\Repository;

use App\Domain\Model\Entity\Invoice;

interface InvoiceRepositoryInterface
{
    public function save(Invoice $invoice): void;
    public function findById(string $id): ?Invoice;
    public function findByOrderId(string $orderId): ?Invoice;
    public function findByClientId(string $clientId, int $page, int $limit): array;
    public function countByClientId(string $clientId): int;
    public function findAll(int $page, int $limit, ?array $sort = null, array $filters = []): array;
    public function countAll(array $filters = []): int;
    public function delete(Invoice $invoice): void;
}