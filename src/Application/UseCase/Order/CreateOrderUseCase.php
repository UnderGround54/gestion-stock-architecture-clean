<?php

namespace App\Application\UseCase\Order;

use App\Application\Factory\OrderFactory;
use App\Application\Transaction\TransactionManagerInterface;
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
        public StockService                $stockService,
        private TransactionManagerInterface $transactionManager
    ) {}

    public function execute(CreateOrderDTO $dto): Order
    {

        return $this->transactionManager->transactional(function () use ($dto) {
            $client = $this->clientRepository->findById($dto->clientId);
            if ($client === null) {
                throw new ClientNotFoundException(
                    "Client introuvable avec l'ID : {$dto->clientId}"
                );
            }

            $order = $this->orderFactory->createOrder($dto->clientId, $dto->customerNote);

            // Traite chaque ligne
            foreach ($dto->orderLines as $lineDTO) {
                $product = $this->productRepository->findById($lineDTO->productId);
                if ($product === null) {
                    throw new ProductNotFoundException(
                        "Produit introuvable avec l'ID : {$lineDTO->productId}"
                    );
                }

                $this->stockService->decreaseStock($product, $lineDTO->quantity);
                $this->productRepository->save($product);

                $line = $this->orderFactory->createLine($product, $lineDTO, $order);
                $order->addLine($line);
            }

            $this->orderRepository->save($order);

            $this->eventDispatcher->dispatch(new OrderCreatedEvent(
                orderId:      $order->getId(),
                clientId:     $order->getClientId(),
                orderNumber:  $order->getNumber(),
                totalAmount:  $order->getTotalAmount()->amount()
            ));

            return $order;
        });
    }
}
