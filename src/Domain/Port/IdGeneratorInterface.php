<?php

namespace App\Domain\Port;

interface IdGeneratorInterface
{
    public function generate(): string;
}
