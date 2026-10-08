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
        private ReflectionHydrator $hydrator
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
            'first_name' => $client->getFirstName(),
            'last_name'  => $client->getLastName(),
            'email'      => $client->getEmail(),
            'phone'      => $client->getPhone(),
            'address'    => $client->getAddress(),
            'is_active'  => $client->isActive() ? 1 : 0,
            'updated_at' => $client->getUpdatedAt()->format('Y-m-d H:i:s'),
        ];

        if (!$exists) {
            $data['created_at'] = $client->getCreatedAt()->format('Y-m-d H:i:s');
        }

        $this->upsert('clients', $data, $exists);
    }

    /**
     * @throws Exception
     */
    public function findById(string $id): ?Client
    {
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM clients WHERE id = ?',
            [$id]
        );

        return $row ? $this->hydrate($row) : null;
    }


    /**
     * @throws Exception
     */
    public function findByEmail(string $email): ?Client
    {
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM clients WHERE email = ?',
            [$email]
        );

        if (!$row) {
            return null;
        }

        return $this->hydrate($row);
    }

    /**
     * Loads all clients with advanced pagination, sorting and filtering.
     *
     * @throws Exception
     */
    public function findAll(int $page, int $limit, ?array $sort = null, array $filters = []): array
    {
        $offset = ($page - 1) * $limit;

        $whereConditions = [];
        $parameters = [];

        foreach ($filters as $field => $value) {
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
                $allowedFields = ['id', 'first_name', 'last_name', 'email', 'phone', 'is_active', 'created_at', 'updated_at'];
                if (in_array($field, $allowedFields)) {
                    $orderByClause = "ORDER BY $field $direction";
                }
            }
        }

        $sql = "SELECT * FROM clients $whereClause $orderByClause LIMIT ? OFFSET ?";
        $parameters[] = $limit;
        $parameters[] = $offset;

        // Déterminer les types de paramètres
        $types = array_fill(0, count($parameters) - 2, ParameterType::STRING); // Pour les filtres
        $types[] = ParameterType::INTEGER; // LIMIT
        $types[] = ParameterType::INTEGER; // OFFSET

        $rows = $this->connection->fetchAllAssociative($sql, $parameters, $types);

        return array_map(fn($row) => $this->hydrate($row), $rows);
    }

    /**
     * Count all clients with optional filtering.
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

        $sql = "SELECT COUNT(*) FROM clients $whereClause";

        // Déterminer les types de paramètres (tous des chaînes pour l'instant)
        $types = array_fill(0, count($parameters), ParameterType::STRING);

        return (int) $this->connection->fetchOne($sql, $parameters, $types);
    }

    /**
     * @throws Exception
     */
    public function existsByEmail(string $email, ?string $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM clients WHERE email = ?';
        $params = [$email];

        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
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
            $row['id'],
            $row['first_name'],
            $row['last_name'],
            $row['email'],
            $row['phone'],
            $row['address'],
            (bool) $row['is_active']
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
