<?php

namespace App\Application\UseCase\Order;

use App\Application\Factory\OrderFactory;
use App\Domain\Event\OrderCreatedEvent;
use App\Domain\Exception\ClientNotFoundException;
use App\Domain\Exception\ProductNotFoundException;
use App\Domain\Model\Entity\Order;
use App\Domain\Model\Repository\ClientRepositoryInterface;
use App\Domain\Model\Repository\OrderRepositoryInterface;
use App\Domain\Model\Repository\ProductRepositoryInterface;
use App\Domain\Port\EventDispatcherInterface;
use App\Domain\Service\StockService;
use App\Presentation\DTO\Request\CreateOrderDTO;

final readonly class CreateOrderUseCase
{
    public function __construct(
        private OrderRepositoryInterface   $orderRepository,
        private ClientRepositoryInterface  $clientRepository,
        private ProductRepositoryInterface $productRepository,
        private EventDispatcherInterface   $eventDispatcher,
        private OrderFactory               $orderFactory,
        public StockService                $stockService
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
        $order = $this->orderFactory->createOrder($dto->clientId, $dto->customerNote);

        // Traite chaque ligne
        foreach ($dto->orderLines as $lineDTO) {
            $product = $this->productRepository->findById($lineDTO->productId);

            if ($product === null) {
                throw new ProductNotFoundException(
                    "Produit introuvable avec l'ID : {$lineDTO->productId}"
                );
            }

            // Règle de gestion: diminuer le stock
            $this->stockService->decreaseStock($product, $lineDTO->quantity);
            $this->productRepository->save($product);

            // Crée la ligne via la Factory
            $line = $this->orderFactory->createLine($product, $lineDTO);
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
