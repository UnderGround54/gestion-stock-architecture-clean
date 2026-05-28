<?php

namespace App\Application\UseCase\Order;

use App\Domain\Model\Repository\OrderRepositoryInterface;
use App\Presentation\DTO\Request\PaginationDTO;


final readonly class ListOrdersUseCase
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository
    ) {}

    public function execute(PaginationDTO $pagination): array
    {
        $orders = $this->orderRepository->findAll($pagination->page, $pagination->limit);
        $total    = $this->orderRepository->countAll();

        return [
            'items'       => $orders,
            'total'       => $total,
            'page'        => $pagination->page,
            'limit'       => $pagination->limit,
            'total_pages' => (int) ceil($total / $pagination->limit),
        ];
    }
}

