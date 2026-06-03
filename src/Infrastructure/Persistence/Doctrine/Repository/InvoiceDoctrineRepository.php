<?php

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Enum\InvoiceStatus;
use App\Domain\Model\Entity\Invoice;
use App\Domain\Model\Repository\InvoiceRepositoryInterface;
use App\Domain\ValueObject\Money;
use App\Infrastructure\Hydration\ReflectionHydrator;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;

final readonly class InvoiceDoctrineRepository extends AbstractDoctrineRepository implements InvoiceRepositoryInterface
{
    public function __construct(
        Connection $connection,
        private ReflectionHydrator $hydrator
    ) {
        parent::__construct($connection);
    }

    /**
     * @throws Exception
     */
    public function save(Invoice $invoice): void
    {
        $exists = (bool) $this->connection->fetchOne('SELECT id FROM invoices WHERE id = ?', [$invoice->getId()]);
        $data = [
            'id'              => $invoice->getId(),
            'number'          => $invoice->getNumber(),
            'order_id'        => $invoice->getOrderId(),
            'client_id'       => $invoice->getClientId(),
            'status'          => $invoice->getStatus()->value,
            'amount_excl_tax' => $invoice->getAmountExclTax()->amount(),
            'tax_rate'        => $invoice->getTaxRate(),
            'tax_amount'      => $invoice->getTaxAmount()->amount(),
            'amount_incl_tax' => $invoice->getAmountInclTax()->amount(),
            'currency'        => $invoice->getAmountInclTax()->currency(),
            'due_date'        => $invoice->getDueDate()->format('Y-m-d H:i:s'),
            'paid_at'         => $invoice->getPaidAt()?->format('Y-m-d H:i:s'),
            'updated_at'      => $invoice->getUpdatedAt()->format('Y-m-d H:i:s'),
        ];
        if (!$exists) {
            $data['created_at'] = $invoice->getCreatedAt()->format('Y-m-d H:i:s');
        }


        $this->upsert('invoices', $data, $exists);
    }

    /**
     * @throws Exception
     */
    public function findById(string $id): ?Invoice
    {
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM invoices WHERE id = ?',
            [$id]
        );

        return $row ? $this->hydrate($row) : null;
    }

    /**
     * @throws Exception
     */
    public function findByOrderId(string $orderId): ?Invoice
    {
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM invoices WHERE order_id = ?',
            [$orderId]
        );

        return $row ? $this->hydrate($row) : null;
    }

    /**
     * @throws Exception
     */
    public function findByClientId(string $clientId, int $page, int $limit): array
    {
        $offset = ($page - 1) * $limit;
        $rows   = $this->connection->fetchAllAssociative(
            'SELECT * FROM invoices WHERE client_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?',
            [$clientId, $limit, $offset],
            [ParameterType::STRING, ParameterType::INTEGER, ParameterType::INTEGER]
        );

        return array_map(fn($row) => $this->hydrate($row), $rows);
    }

    /**
     * @throws Exception
     */
    public function countByClientId(string $clientId): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM invoices WHERE client_id = ?',
            [$clientId]
        );
    }

    /**
     * @throws Exception
     */
    public function findAll(int $page, int $limit): array
    {
        $offset = ($page - 1) * $limit;
        $rows   = $this->connection->fetchAllAssociative(
            'SELECT * FROM invoices ORDER BY created_at DESC LIMIT ? OFFSET ?',
            [$limit, $offset],
            [ParameterType::INTEGER, ParameterType::INTEGER]
        );

        return array_map(fn($row) => $this->hydrate($row), $rows);
    }

    /**
     * @throws Exception
     */
    public function countAll(): int
    {
        return (int) $this->connection->fetchOne('SELECT COUNT(*) FROM invoices');
    }

    /**
     * @throws \Exception
     */
    private function hydrate(array $row): Invoice
    {
        $invoice = new Invoice(
            $row['id'],
            $row['order_id'],
            $row['client_id'],
            Money::of((float) $row['amount_excl_tax'], $row['currency']),
            (float) $row['tax_rate']
        );

        $this->hydrator->setMany($invoice, [
            'id'            => $row['id'],
            'number'        => $row['number'],
            'status'        => InvoiceStatus::from($row['status']),
            'taxAmount'     => Money::of((float) $row['tax_amount'], $row['currency']),
            'amountInclTax' => Money::of((float) $row['amount_incl_tax'], $row['currency']),
            'dueDate'       => new \DateTimeImmutable($row['due_date']),
            'paidAt'        => $row['paid_at'] ? new \DateTimeImmutable($row['paid_at']) : null,
            'createdAt'     => new \DateTimeImmutable($row['created_at']),
            'updatedAt'     => new \DateTimeImmutable($row['updated_at']),
        ]);

        return $invoice;
    }
}
