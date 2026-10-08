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
            'excl_tax_amount' => $invoice->getAmountExclTax()->amount(),
            'excl_tax_currency' => $invoice->getAmountExclTax()->currency(),
            'tax_rate'        => $invoice->getTaxRate(),
            'tax_amount'      => $invoice->getTaxAmount()->amount(),
            'tax_currency'    => $invoice->getTaxAmount()->currency(),
            'incl_tax_amount' => $invoice->getAmountInclTax()->amount(),
            'incl_tax_currency' => $invoice->getAmountInclTax()->currency(),
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

        return $this->hydrateCollection($rows);
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
     * Loads all invoices with advanced pagination, sorting and filtering.
     *
     * @throws Exception
     */
    public function findAll(int $page, int $limit, ?array $sort = null, array $filters = []): array
    {
        $offset = ($page - 1) * $limit;

        // Construire la requête WHERE dynamique basée sur les filtres
        $whereConditions = [];
        $parameters = [];

        foreach ($filters as $field => $value) {
            // Pour l'instant, on supporte seulement l'égalité exacte
            $whereConditions[] = "$field = ?";
            $parameters[] = $value;
        }

        $whereClause = '';
        if (!empty($whereConditions)) {
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        }

        // Déterminer l'ordre de tri
        $orderByClause = 'ORDER BY created_at DESC'; // Défaut
        if ($sort !== null && count($sort) === 2) {
            $field = $sort[0];
            $direction = strtoupper($sort[1]);

            // Valider que la direction est soit ASC soit DESC
            if ($direction === 'ASC' || $direction === 'DESC') {
                // Pour l'instant, on autorise seulement certains champs pour des raisons de sécurité
                $allowedFields = ['id', 'number', 'status', 'client_id', 'order_id', 'tax_rate', 'due_date', 'paid_at', 'created_at', 'updated_at'];
                if (in_array($field, $allowedFields)) {
                    $orderByClause = "ORDER BY $field $direction";
                }
            }
        }

        $sql = "SELECT * FROM invoices $whereClause $orderByClause LIMIT ? OFFSET ?";
        $parameters[] = $limit;
        $parameters[] = $offset;

        // Déterminer les types de paramètres
        $types = array_fill(0, count($parameters) - 2, ParameterType::STRING); // Pour les filtres
        $types[] = ParameterType::INTEGER; // LIMIT
        $types[] = ParameterType::INTEGER; // OFFSET

        $rows = $this->connection->fetchAllAssociative($sql, $parameters, $types);

        return $this->hydrateCollection($rows);
    }

    /**
     * Count all invoices with optional filtering.
     *
     * @throws Exception
     */
    public function countAll(array $filters = []): int
    {
        // Construire la requête WHERE dynamique basée sur les filtres
        $whereConditions = [];
        $parameters = [];

        foreach ($filters as $field => $value) {
            // Pour l'instant, on supporte seulement l'égalité exacte
            $whereConditions[] = "$field = ?";
            $parameters[] = $value;
        }

        $whereClause = '';
        if (!empty($whereConditions)) {
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        }

        $sql = "SELECT COUNT(*) FROM invoices $whereClause";

        // Déterminer les types de paramètres (tous des chaînes pour l'instant)
        $types = array_fill(0, count($parameters), ParameterType::STRING);

        return (int) $this->connection->fetchOne($sql, $parameters, $types);
    }

    /**
     * @throws Exception
     */
    public function delete(Invoice $invoice): void
    {
        $this->connection->delete('invoices', ['id' => $invoice->getId()]);
    }

    /**
     * Fetches all invoices for a set of invoices in a single query,
     * then groups them and hydrates — O(2) queries instead of O(N+1).
     *
     * @param array<array<string, mixed>> $invoiceRows
     * @return Invoice[]
     * @throws Exception
     */
    private function hydrateCollection(array $invoiceRows): array
    {
        if (empty($invoiceRows)) {
            return [];
        }

        $invoiceIds    = array_column($invoiceRows, 'id');
        $placeholders = implode(',', array_fill(0, count($invoiceIds), '?'));

        // Pas de relations à hydrater pour les factures dans cette implémentation simple
        // Si on avait des lignes de facture, on les hydraterait ici

        return array_map(
            fn(array $row) => $this->hydrate($row),
            $invoiceRows
        );
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
            Money::of((float) $row['excl_tax_amount'], $row['excl_tax_currency']),
            (float) $row['tax_rate']
        );

        $this->hydrator->setMany($invoice, [
            'id'            => $row['id'],
            'number'        => $row['number'],
            'status'        => InvoiceStatus::from($row['status']),
            'taxAmount'     => Money::of((float) $row['tax_amount'], $row['tax_currency']),
            'amountInclTax' => Money::of((float) $row['incl_tax_amount'], $row['incl_tax_currency']),
            'dueDate'       => new \DateTimeImmutable($row['due_date']),
            'paidAt'        => $row['paid_at'] ? new \DateTimeImmutable($row['paid_at']) : null,
            'createdAt'     => new \DateTimeImmutable($row['created_at']),
            'updatedAt'     => new \DateTimeImmutable($row['updated_at']),
        ]);

        return $invoice;
    }
}
