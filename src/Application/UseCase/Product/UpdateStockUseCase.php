<?php

namespace App\Application\UseCase\Product;

use App\Application\DTO\Request\UpdateStockDTO;
use App\Domain\Entity\Product;
use App\Domain\Event\InsufficientStockEvent;
use App\Domain\Exception\ProductNotFoundException;
use App\Domain\Repository\ProductRepositoryInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final readonly class UpdateStockUseCase
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private EventDispatcherInterface   $eventDispatcher
    ) {}

    public function execute(UpdateStockDTO $dto): Product
    {
        $product = $this->productRepository->findById($dto->productId);

        if ($product === null) {
            throw new ProductNotFoundException(
                "Produit introuvable avec l'ID : {$dto->productId}"
            );
        }

        match ($dto->operation) {
            'increase' => $product->increaseStock($dto->quantity),
            'decrease' => $product->decreaseStock($dto->quantity),
        };

        $this->productRepository->save($product);

        // Déclenche un event si le stock passe sous le minimum
        if ($product->isBelowMinimumStock()) {
            $this->eventDispatcher->dispatch(new InsufficientStockEvent(
                productId:       $product->getId(),
                productName:     $product->getName(),
                currentQuantity: $product->getStockQuantity(),
                minimumStock:    $product->getMinimumStock()
            ));
        }

        return $product;
    }
}
