<?php

namespace App\Application\UseCase\Product;

use App\Domain\Exception\ProductNotFoundException;
use App\Domain\Model\Entity\Product;
use App\Domain\Model\Repository\ProductRepositoryInterface;

final readonly class GetProductUseCase
{
    public function __construct(
        private ProductRepositoryInterface $repository,
    ) {}

    public function execute(string $id): Product
    {
        $product = $this->repository->findById($id);

        if ($product === null) {
            throw new ProductNotFoundException("Produit introuvable avec l'ID : {$id}");
        }

        return $product;
    }
}
