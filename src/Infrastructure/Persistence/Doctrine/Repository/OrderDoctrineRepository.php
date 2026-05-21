<?php

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Enum\OrderStatus;
use App\Domain\Model\Entity\Order;
use App\Domain\Model\Entity\OrderLine;
use App\Domain\Model\Repository\OrderRepositoryInterface;
use App\Domain\ValueObject\Money;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;

final readonly class OrderDoctrineRepository implements OrderRepositoryInterface
{
    public function __construct(private Connection $connection) {}

    /**
     * @throws Exception
     */
    public function save(Order $order): void
    {
        $exists = $this->connection->fetchOne(
            'SELECT id FROM orders WHERE id = ?',
            [$order->getId()]
        );

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

        if ($exists) {
            $this->connection->update('orders', $data, ['id' => $order->getId()]);
        } else {
            $data['created_at'] = $order->getCreatedAt()->format('Y-m-d H:i:s');
            $this->connection->insert('orders', $data);
        }

        // Upsert des lignes
        foreach ($order->getOrderLines() as $line) {
            $this->saveLine($line, $order->getId());
        }
    }

    /**
     * @throws Exception
     */
    private function saveLine(OrderLine $line, string $orderId): void
    {
        $exists = $this->connection->fetchOne(
            'SELECT id FROM order_lines WHERE id = ?',
            [$line->getId()]
        );

        $data = [
            'id'                => $line->getId(),
            'order_id'          => $orderId,
            'product_id'        => $line->getProductId(),
            'product_name'      => $line->getProductName(),
            'reference'         => $line->getProductReference(),
            'quantity'          => $line->getQuantity(),
            'unit_price'        => $line->getUnitPrice()->amount(),
            'currency'          => $line->getUnitPrice()->currency(),
            'sub_total'         => $line->getSubTotal()->amount(),
        ];

        if (!$exists) {
            $this->connection->insert('order_lines', $data);
        }
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

        return array_map(fn($row) => $this->findById($row['id']), $rows);
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

        return array_map(fn($row) => $this->findById($row['id']), $rows);
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
     * @throws \ReflectionException
     * @throws \Exception
     */
    private function hydrate(array $row, array $lineRows): Order
    {
        $order = new Order($row['client_id'], $row['customer_note'] ?? '');

        $ref = new \ReflectionClass($order);

        foreach (['id', 'number', 'customerNote', 'createdAt', 'updatedAt'] as $prop) {
            $p = $ref->getProperty($prop);
            $p->setAccessible(true);
        }

        $ref->getProperty('id')->setValue($order, $row['id']);
        $ref->getProperty('number')->setValue($order, $row['number']);
        $ref->getProperty('status')->setValue($order, OrderStatus::from($row['status']));
        $ref->getProperty('totalAmount')->setValue($order, Money::of((float) $row['total_amount'], $row['currency']));
        $ref->getProperty('createdAt')->setValue($order, new \DateTimeImmutable($row['created_at']));
        $ref->getProperty('updatedAt')->setValue($order, new \DateTimeImmutable($row['updated_at']));

        $lines = array_map(fn($l) => $this->hydrateLine($l), $lineRows);
        $ref->getProperty('orderLines')->setValue($order, $lines);

        return $order;
    }

    private function hydrateLine(array $row): OrderLine
    {
        $line = new OrderLine(
            $row['product_id'],
            $row['product_name'],
            $row['reference'],
            (int) $row['quantity'],
            Money::of((float) $row['unit_price'], $row['currency'])
        );

        $ref  = new \ReflectionClass($line);
        $idPp = $ref->getProperty('id');
        $idPp->setAccessible(true);
        $idPp->setValue($line, $row['id']);

        return $line;
    }
}
