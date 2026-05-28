<?php

namespace App\Infrastructure\Service;


use App\Domain\Port\IdGeneratorInterface;
use Symfony\Component\Uid\Uuid;

final class UuidGenerator implements IdGeneratorInterface
{
    public function generate(): string
    {
        return Uuid::v4()->toRfc4122();
    }
}
