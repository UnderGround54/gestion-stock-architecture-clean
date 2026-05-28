<?php

namespace App\Domain\Port;

interface EventDispatcherInterface
{
    public function dispatch(object $event): void;
}
