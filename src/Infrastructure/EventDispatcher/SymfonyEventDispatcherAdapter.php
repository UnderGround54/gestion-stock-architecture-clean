<?php

namespace App\Infrastructure\EventDispatcher;

use App\Domain\Port\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface as SymfonyDispatcher;

final readonly class SymfonyEventDispatcherAdapter implements EventDispatcherInterface
{
    public function __construct(private SymfonyDispatcher $dispatcher) {}
    public function dispatch(object $event): void
    {
        $this->dispatcher->dispatch($event);
    }
}
