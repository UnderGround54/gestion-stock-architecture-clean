<?php

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Enum\OrderStatus;
use App\Domain\Model\Entity\Order;
use App\Domain\Model\Entity\OrderLine;
use App\Domain\Model\Repository\OrderRepositoryInterface;
use App\Domain\Port\IdGeneratorInterface;
use App\Domain\ValueObject\Money;
use App\Infrastructure\Hydration\ReflectionHydrator;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;

final readonly class OrderDoctrineRepository extends AbstractDoctrineRepository implements OrderRepositoryInterface
{
    public function __construct(
        Connection $connection,
        private ReflectionHydrator $hydrator,
        private IdGeneratorInterface $idGenerator,
    ) {
        parent::__construct($connection);
    }

    /**
     * @throws Exception
     */
    public function save(Order $order): void
    {
        $exists = (bool) $this->connection->fetchOne('SELECT id FROM orders WHERE id = ?', [$order->getId()]);
        $data = [
            'id'            => $order->getId(),
            'number'        => $order->getNumber(),
            'client_id'     => $order->getClientId(),
            'status'        => $order->getStatus()->value,
            'total_amount'  => $order->getTotalAmount()->amount(),
            'currency'      => $order->getTotalAmount()->currency(),
            'customer_note' => $order->getCustomerNote(),
            'updated_at'    => $order->getUpdatedAt()->format('Y-m-d H:i:s'),
        ];

        if (!$exists) {
            $data['created_at'] = $order->getCreatedAt()->format('Y-m-d H:i:s');
        }

        $this->upsert('orders', $data, $exists);

        foreach ($order->getOrderLines() as $line) {
            $this->saveLine($line, $order->getId());
        }
    }

    /**
     * @throws Exception
     */
    private function saveLine(OrderLine $line, string $orderId): void
    {
        // Lines are immutable once created: INSERT only, no UPDATE
        $exists = $this->connection->fetchOne(
            'SELECT id FROM order_lines WHERE id = ?',
            [$line->getId()]
        );

        if ($exists) {
            return;
        }

        $this->connection->insert('order_lines', [
            'id'           => $line->getId(),
            'order_id'     => $orderId,
            'product_id'   => $line->getProductId(),
            'product_name' => $line->getProductName(),
            'reference'    => $line->getProductReference(),
            'quantity'     => $line->getQuantity(),
            'unit_price'   => $line->getUnitPrice()->amount(),
            'currency'     => $line->getUnitPrice()->currency(),
            'sub_total'    => $line->getSubTotal()->amount(),
        ]);
    }

    /**
     * @throws Exception
     */
    public function findById(string $id): ?Order
    {
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM orders WHERE id = ?',
            [$id]
        );

        if (!$row) {
            return null;
        }

        $lines = $this->connection->fetchAllAssociative(
            'SELECT * FROM order_lines WHERE order_id = ?',
            [$id]
        );

        return $this->hydrate($row, $lines);
    }

    /**
     * Loads all orders for a client in 2 queries instead of N+1.
     *
     * @throws Exception
     */
    public function findByClientId(string $clientId, int $page, int $limit): array
    {
        $offset = ($page - 1) * $limit;
        $rows   = $this->connection->fetchAllAssociative(
            'SELECT * FROM orders WHERE client_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?',
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
            'SELECT COUNT(*) FROM orders WHERE client_id = ?',
            [$clientId]
        );
    }

    /**
     * Loads all orders in 2 queries instead of N+1.
     *
     * @throws Exception
     */
    public function findAll(int $page, int $limit): array
    {
        $offset = ($page - 1) * $limit;
        $rows   = $this->connection->fetchAllAssociative(
            'SELECT * FROM orders ORDER BY created_at DESC LIMIT ? OFFSET ?',
            [$limit, $offset],
            [ParameterType::INTEGER, ParameterType::INTEGER]
        );

        return $this->hydrateCollection($rows);
    }

    /**
     * @throws Exception
     */
    public function countAll(): int
    {
        return (int) $this->connection->fetchOne('SELECT COUNT(*) FROM orders');
    }

    /**
     * @throws Exception
     */
    public function delete(Order $order): void
    {
        $this->connection->delete('order_lines', ['order_id' => $order->getId()]);
        $this->connection->delete('orders', ['id' => $order->getId()]);
    }

    /**
     * Fetches all order_lines for a set of orders in a single query,
     * then groups them and hydrates — O(2) queries instead of O(N+1).
     *
     * @param array<array<string, mixed>> $orderRows
     * @return Order[]
     * @throws Exception
     */
    private function hydrateCollection(array $orderRows): array
    {
        if (empty($orderRows)) {
            return [];
        }

        $orderIds    = array_column($orderRows, 'id');
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));

        $lineRows = $this->connection->fetchAllAssociative(
            "SELECT * FROM order_lines WHERE order_id IN ({$placeholders})",
            $orderIds
        );

        // Group lines by order_id for O(1) lookup during hydration
        $linesByOrder = [];
        foreach ($lineRows as $lineRow) {
            $linesByOrder[$lineRow['order_id']][] = $lineRow;
        }

        return array_map(
            fn(array $row) => $this->hydrate($row, $linesByOrder[$row['id']] ?? []),
            $orderRows
        );
    }

    /**
     * @throws \Exception
     */
    private function hydrate(array $row, array $lineRows): Order
    {
        $id = $this->idGenerator->generate();
        $order = new Order($id, $row['client_id'], $row['customer_note'] ?? '');

        $this->hydrator->setMany($order, [
            'id'          => $row['id'],
            'number'      => $row['number'],
            'status'      => OrderStatus::from($row['status']),
            'totalAmount' => Money::of((float) $row['total_amount'], $row['currency']),
            'createdAt'   => new \DateTimeImmutable($row['created_at']),
            'updatedAt'   => new \DateTimeImmutable($row['updated_at']),
            'orderLines'  => array_map(fn($l) => $this->hydrateLine($l), $lineRows),
        ]);

        return $order;
    }

    /**
     * @throws \ReflectionException
     */
    private function hydrateLine(array $row): OrderLine
    {
        $line = new OrderLine(
            $this->idGenerator->generate(),
            $row['product_id'],
            $row['product_name'],
            $row['reference'],
            (int) $row['quantity'],
            Money::of((float) $row['unit_price'], $row['currency'])
        );

        $this->hydrator->set($line, 'id', $row['id']);

        return $line;
    }
}
