<?php

namespace App\Application\UseCase\Order;

use App\Domain\Exception\orderNotFoundException;
use App\Domain\Model\Entity\order;
use App\Domain\Model\Repository\OrderRepositoryInterface;

final readonly class GetOrderUseCase
{
    public function __construct(
        private OrderRepositoryInterface $repository,
    ) {}

    public function execute(string $id): Order
    {
        $order = $this->repository->findById($id);

        if ($order === null) {
            throw new OrderNotFoundException("Commande introuvable avec l'ID : {$id}");
        }

        return $order;
    }
}
