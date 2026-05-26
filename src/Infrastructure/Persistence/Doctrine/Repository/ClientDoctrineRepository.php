<?php

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Model\Entity\Client;
use App\Domain\Model\Repository\ClientRepositoryInterface;
use App\Infrastructure\Hydration\ReflectionHydrator;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;

final readonly class ClientDoctrineRepository extends AbstractDoctrineRepository implements ClientRepositoryInterface
{
    public function __construct(
        Connection $connection,
        private ReflectionHydrator $hydrator,
    ) {
        parent::__construct($connection);
    }

    /**
     * @throws Exception
     */
    public function save(Client $client): void
    {
        $exists = (bool) $this->connection->fetchOne('SELECT id FROM clients WHERE id = ?', [$client->getId()]);

        $data = [
            'id'         => $client->getId(),
            'last_name'  => $client->getLastName(),
            'first_name' => $client->getFirstName(),
            'email'      => $client->getEmail(),
            'phone'      => $client->getPhone(),
            'address'    => $client->getAddress(),
            'is_active'  => (int) $client->isActive(),
            'updated_at' => $client->getUpdatedAt()->format('Y-m-d H:i:s'),
        ];

        $this->upsert('clients', $data, $exists);
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function findById(string $id): ?Client
    {
        $row = $this->connection->fetchAssociative('SELECT * FROM clients WHERE id = ?', [$id]);

        return $row ? $this->hydrate($row) : null;
    }


    /**
     * @throws Exception
     * @throws \Exception
     */
    public function findByEmail(string $email): ?Client
    {
        $row = $this->connection->fetchAssociative('SELECT * FROM clients WHERE email = ?', [$email]);

        return $row ? $this->hydrate($row) : null;
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function findAll(int $page, int $limit): array
    {
        $offset = ($page - 1) * $limit;
        $rows   = $this->connection->fetchAllAssociative(
            'SELECT * FROM clients ORDER BY created_at DESC LIMIT ? OFFSET ?',
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
        return (int) $this->connection->fetchOne('SELECT COUNT(*) FROM clients');
    }

    /**
     * @throws Exception
     */
    public function existsByEmail(string $email, ?string $excludeId = null): bool
    {
        $sql    = 'SELECT COUNT(*) FROM clients WHERE email = ?';
        $params = [$email];

        if ($excludeId !== null) {
            $sql      .= ' AND id != ?';
            $params[]  = $excludeId;
        }

        return (int) $this->connection->fetchOne($sql, $params) > 0;
    }

    /**
     * @throws Exception
     */
    public function delete(Client $client): void
    {
        $this->connection->delete('clients', ['id' => $client->getId()]);
    }

    /**
     * @throws \Exception
     */
    private function hydrate(array $row): Client
    {
        $client = new Client(
            $row['last_name'],
            $row['first_name'],
            $row['email'],
            $row['phone'],
            $row['address']
        );

        $this->hydrator->setMany($client, [
            'id'        => $row['id'],
            'createdAt' => new \DateTimeImmutable($row['created_at']),
            'updatedAt' => new \DateTimeImmutable($row['updated_at']),
        ]);

        if (!(bool) $row['is_active']) {
            $this->hydrator->set($client, 'isActive', false);
        }

        return $client;
    }
}
