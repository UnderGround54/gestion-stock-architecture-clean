<?php

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

abstract readonly class AbstractDoctrineRepository
{
    public function __construct(protected Connection $connection) {}

    /**
     * Insert or update a row in the given table based on whether the ID already exists.
     *
     * @throws Exception
     */
    protected function upsert(string $table, array $data, bool $exists): void
    {
        $id = $data['id'];

        if ($exists) {
            $this->connection->update($table, $data, ['id' => $id]);
        } else {
            $this->connection->insert($table, $data);
        }
    }
}
