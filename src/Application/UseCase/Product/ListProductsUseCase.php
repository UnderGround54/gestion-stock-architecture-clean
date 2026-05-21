<?php

namespace App\Application\UseCase\Product;

use App\Application\DTO\Request\PaginationDTO;
use App\Domain\Repository\ProductRepositoryInterface;


final readonly class ListProductsUseCase
{
    public function __construct(
        private ProductRepositoryInterface $productRepository
    ) {}

    public function execute(PaginationDTO $pagination): array
    {
        $products = $this->productRepository->findAll($pagination->page, $pagination->limit);
        $total    = $this->productRepository->countAll();

        return [
            'items'       => $products,
            'total'       => $total,
            'page'        => $pagination->page,
            'limit'       => $pagination->limit,
            'total_pages' => (int) ceil($total / $pagination->limit),
        ];
    }
}

