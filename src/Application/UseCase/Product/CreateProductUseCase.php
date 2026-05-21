<?php

namespace App\Application\UseCase\Product;

use App\Application\DTO\Request\CreateProductDTO;
use App\Domain\Exception\InvalidProductException;
use App\Domain\Model\Entity\Product;
use App\Domain\Model\Repository\ProductRepositoryInterface;
use App\Domain\ValueObject\Money;

final readonly class CreateProductUseCase
{
    public function __construct(
        private ProductRepositoryInterface $productRepository
    ) {}

    public function execute(CreateProductDTO $dto): Product
    {
        // Règle de gestion: référence unique
        if ($this->productRepository->existsByReference($dto->reference)) {
            throw new InvalidProductException(
                "Un produit avec la référence '{$dto->reference}' existe déjà."
            );
        }

        $product = new Product(
            name:          $dto->name,
            reference:     $dto->reference,
            description:   $dto->description,
            price:         Money::of($dto->price, $dto->currency),
            stockQuantity: $dto->stockQuantity,
            minimumStock:  $dto->minimumStock
        );

        $this->productRepository->save($product);

        return $product;
    }
}
