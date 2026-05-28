<?php

namespace App\Application\UseCase\Product;

use App\Domain\Exception\InvalidProductException;
use App\Domain\Model\Entity\Product;
use App\Domain\Model\Repository\ProductRepositoryInterface;
use App\Domain\Port\IdGeneratorInterface;
use App\Domain\ValueObject\Money;
use App\Presentation\DTO\Request\CreateProductDTO;

final readonly class CreateProductUseCase
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private IdGeneratorInterface $idGenerator
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
            id:            $this->idGenerator->generate(),
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
