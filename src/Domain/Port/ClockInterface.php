<?php

namespace App\Domain\Port;

interface ClockInterface
{
    public function now(): \DateTimeImmutable;
}
