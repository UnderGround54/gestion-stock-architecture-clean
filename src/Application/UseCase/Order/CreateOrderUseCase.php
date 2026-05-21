<?php

namespace App\Application\UseCase\Order;

use App\Application\DTO\Request\CreateOrderDTO;
use App\Application\Factory\OrderFactory;
use App\Domain\Entity\Order;
use App\Domain\Event\OrderCreatedEvent;
use App\Domain\Exception\ClientNotFoundException;
use App\Domain\Exception\InvalidProductException;
use App\Domain\Exception\ProductNotFoundException;
use App\Domain\Repository\ClientRepositoryInterface;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\Repository\ProductRepositoryInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final readonly class CreateOrderUseCase
{
    public function __construct(
        private OrderRepositoryInterface   $orderRepository,
        private ClientRepositoryInterface  $clientRepository,
        private ProductRepositoryInterface $productRepository,
        private EventDispatcherInterface   $eventDispatcher
    ) {}

    public function execute(CreateOrderDTO $dto): Order
    {
        // Vérifie que le client existe
        $client = $this->clientRepository->findById($dto->clientId);
        if ($client === null) {
            throw new ClientNotFoundException(
                "Client introuvable avec l'ID : {$dto->clientId}"
            );
        }

        // Crée la commande via la Factory
        $order = OrderFactory::create($dto->clientId, $dto->customerNote);

        // Traite chaque ligne
        foreach ($dto->orderLines as $lineDTO) {
            $product = $this->productRepository->findById($lineDTO->productId);

            if ($product === null) {
                throw new ProductNotFoundException(
                    "Produit introuvable avec l'ID : {$lineDTO->productId}"
                );
            }

            if (!$product->isAvailable()) {
                throw new InvalidProductException(
                    "Le produit '{$product->getName()}' n'est pas disponible."
                );
            }

            // Règle de gestion: diminuer le stock
            $product->decreaseStock($lineDTO->quantity);
            $this->productRepository->save($product);

            // Crée la ligne via la Factory
            $line = OrderFactory::createLine($product, $lineDTO);
            $order->addLine($line);
        }

        $this->orderRepository->save($order);

        // Émet l'event CommandeCreee
        $this->eventDispatcher->dispatch(new OrderCreatedEvent(
            orderId:      $order->getId(),
            clientId:     $order->getClientId(),
            orderNumber:  $order->getNumber(),
            totalAmount:  $order->getTotalAmount()->amount()
        ));

        return $order;
    }
}
