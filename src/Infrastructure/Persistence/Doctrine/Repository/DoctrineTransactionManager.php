<?php
namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Application\Transaction\TransactionManagerInterface;
use Doctrine\ORM\EntityManagerInterface;

readonly class DoctrineTransactionManager implements TransactionManagerInterface
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    public function transactional(callable $callback): mixed
    {
        return $this->em->wrapInTransaction($callback);
    }
}

