<?php

namespace App\Application\Transaction;

interface TransactionManagerInterface
{
    public function transactional(callable $callback): mixed;
}
