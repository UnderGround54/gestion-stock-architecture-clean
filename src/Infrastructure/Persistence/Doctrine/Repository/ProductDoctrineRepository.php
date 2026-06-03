<?php

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Model\Entity\Product;
use App\Domain\Model\Repository\ProductRepositoryInterface;
use App\Domain\ValueObject\Money;
use App\Infrastructure\Hydration\ReflectionHydrator;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;

final readonly class ProductDoctrineRepository extends AbstractDoctrineRepository implements ProductRepositoryInterface
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
    public function save(Product $product): void
    {
        $exists = (bool) $this->connection->fetchOne('SELECT id FROM products WHERE id = ?', [$product->getId()]);
        $data = [
            'id'             => $product->getId(),
            'name'           => $product->getName(),
            'reference'      => $product->getReference(),
            'description'    => $product->getDescription(),
            'price'          => $product->getPrice()->amount(),
            'currency'       => $product->getPrice()->currency(),
            'stock_quantity' => $product->getStockQuantity(),
            'minimum_stock'  => $product->getMinimumStock(),
            'is_active'      => (int) $product->isActive(),
            'updated_at'     => $product->getUpdatedAt()->format('Y-m-d H:i:s'),
        ];

        if (!$exists) {
            $data['created_at'] = $product->getCreatedAt()->format('Y-m-d H:i:s');
        }

        $this->upsert('products', $data, $exists);
    }

    /**
     * @throws Exception
     */
    public function findById(string $id): ?Product
    {
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM products WHERE id = ?',
            [$id]
        );

        return $row ? $this->hydrate($row) : null;
    }

    /**
     * @throws Exception
     */
    public function findByReference(string $reference): ?Product
    {
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM products WHERE reference = ?',
            [$reference]
        );

        return $row ? $this->hydrate($row) : null;
    }

    /**
     * @throws Exception
     */
    public function findAll(int $page, int $limit): array
    {
        $offset = ($page - 1) * $limit;
        $rows   = $this->connection->fetchAllAssociative(
            'SELECT * FROM products ORDER BY created_at DESC LIMIT ? OFFSET ?',
            [$limit, $offset],
            [ParameterType::INTEGER, ParameterType::INTEGER]
        );

        return array_map(fn(array $row) => $this->hydrate($row), $rows);
    }

    /**
     * @throws Exception
     */
    public function countAll(): int
    {
        return (int) $this->connection->fetchOne('SELECT COUNT(*) FROM products');
    }

    /**
     * @throws Exception
     */
    public function findActive(int $page, int $limit): array
    {
        $offset = ($page - 1) * $limit;
        $rows   = $this->connection->fetchAllAssociative(
            'SELECT * FROM products WHERE is_active = 1 ORDER BY name ASC LIMIT ? OFFSET ?',
            [$limit, $offset],
            [ParameterType::INTEGER, ParameterType::INTEGER]
        );

        return array_map(fn(array $row) => $this->hydrate($row), $rows);
    }

    /**
     * @throws Exception
     */
    public function existsByReference(string $reference, ?string $excludeId = null): bool
    {
        $sql    = 'SELECT COUNT(*) FROM products WHERE reference = ?';
        $params = [$reference];

        if ($excludeId !== null) {
            $sql      .= ' AND id != ?';
            $params[]  = $excludeId;
        }

        return (int) $this->connection->fetchOne($sql, $params) > 0;
    }

    /**
     * @throws Exception
     */
    public function delete(Product $product): void
    {
        $this->connection->delete('products', ['id' => $product->getId()]);
    }

    /**
     * @throws \Exception
     */
    private function hydrate(array $row): Product
    {
        $product = new Product(
            id:            $row['id'],
            name:          $row['name'],
            reference:     $row['reference'],
            description:   $row['description'],
            price:         Money::of((float) $row['price'], $row['currency']),
            stockQuantity: (int) $row['stock_quantity'],
            minimumStock:  (int) $row['minimum_stock']
        );

        $this->hydrator->setMany($product, [
            'id'        => $row['id'],
            'createdAt' => new \DateTimeImmutable($row['created_at']),
            'updatedAt' => new \DateTimeImmutable($row['updated_at']),
        ]);

        if (!(bool) $row['is_active']) {
            $this->hydrator->set($product, 'isActive', false);
        }

        return $product;
    }
}

