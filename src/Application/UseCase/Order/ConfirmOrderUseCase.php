<?php

namespace App\Application\UseCase\Order;

use App\Domain\Event\OrderConfirmedEvent;
use App\Domain\Exception\OrderNotFoundException;
use App\Domain\Model\Entity\Order;
use App\Domain\Model\Repository\OrderRepositoryInterface;
use App\Domain\Port\EventDispatcherInterface;


final readonly class ConfirmOrderUseCase
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private EventDispatcherInterface $eventDispatcher
    ) {}

    public function execute(string $orderId): Order
    {
        $order = $this->orderRepository->findById($orderId);

        if ($order === null) {
            throw new OrderNotFoundException(
                "Commande introuvable avec l'ID : {$orderId}"
            );
        }

        $order->confirm();
        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatch(new OrderConfirmedEvent(
            orderId:     $order->getId(),
            clientId:    $order->getClientId(),
            orderNumber: $order->getNumber()
        ));

        return $order;
    }
}

