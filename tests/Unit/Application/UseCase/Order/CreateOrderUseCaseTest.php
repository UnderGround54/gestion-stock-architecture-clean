<?php

namespace App\Tests\Unit\Application\UseCase\Order;

use App\Application\Factory\OrderFactory;
use App\Application\UseCase\Order\CreateOrderUseCase;
use App\Domain\Event\OrderCreatedEvent;
use App\Domain\Exception\ClientNotFoundException;
use App\Domain\Exception\ProductNotFoundException;
use App\Domain\Model\Entity\Client;
use App\Domain\Model\Entity\Order;
use App\Domain\Model\Entity\Product;
use App\Domain\Model\Repository\ClientRepositoryInterface;
use App\Domain\Model\Repository\OrderRepositoryInterface;
use App\Domain\Model\Repository\ProductRepositoryInterface;
use App\Domain\Port\EventDispatcherInterface;
use App\Domain\Port\IdGeneratorInterface;
use App\Domain\Service\StockService;
use App\Domain\ValueObject\Money;
use App\Presentation\DTO\Request\CreateOrderDTO;
use App\Presentation\DTO\Request\OrderLineDTO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(Order::class)]
final class CreateOrderUseCaseTest extends TestCase
{
    private OrderRepositoryInterface&MockObject   $orderRepository;
    private ClientRepositoryInterface&MockObject  $clientRepository;
    private ProductRepositoryInterface&MockObject $productRepository;
    private EventDispatcherInterface&MockObject   $eventDispatcher;
    private StockService&MockObject               $stockService;
    private CreateOrderUseCase                    $useCase;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->orderRepository   = $this->createMock(OrderRepositoryInterface::class);
        $this->clientRepository  = $this->createMock(ClientRepositoryInterface::class);
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $this->eventDispatcher   = $this->createMock(EventDispatcherInterface::class);
        $this->stockService      = $this->createMock(StockService::class);

        $idGenerator  = $this->createIdGenerator();
        $orderFactory = new OrderFactory($idGenerator);

        $this->useCase = new CreateOrderUseCase(
            $this->orderRepository,
            $this->clientRepository,
            $this->productRepository,
            $this->eventDispatcher,
            $orderFactory,
            $this->stockService
        );
    }

    // ------------------------------------------------------------------ helpers

    /**
     * @throws Exception
     */
    private function createIdGenerator(): IdGeneratorInterface
    {
        $generator = $this->createMock(IdGeneratorInterface::class);
        $generator->method('generate')->willReturnCallback(
            static fn() => 'generated-uuid-' . bin2hex(random_bytes(4))
        );
        return $generator;
    }

    /**
     * @throws Exception
     */
    private function makeClient(): Client
    {
        $client = $this->createMock(Client::class);
        $client->method('getId')->willReturn('client-001');
        return $client;
    }

    /**
     * @throws Exception
     */
    private function makeProduct(float $price = 20.0): Product
    {
        $product = $this->createMock(Product::class);
        $product->method('getId')->willReturn('product-001');
        $product->method('getName')->willReturn('Widget');
        $product->method('getReference')->willReturn('REF-001');
        $product->method('getPrice')->willReturn(Money::of($price));
        return $product;
    }

    private function makeDTO(): CreateOrderDTO
    {
        if (empty($lines)) {
            $lines = [new OrderLineDTO('product-001', 2)];
        }
        return new CreateOrderDTO('client-001', $lines, 'please handle with care');
    }

    // ------------------------------------------------------------------ success

    /**
     * @throws Exception
     */
    public function testExecuteReturnsOrder(): void
    {
        $this->clientRepository->method('findById')->willReturn($this->makeClient());
        $this->productRepository->method('findById')->willReturn($this->makeProduct());
        $this->orderRepository->expects(self::once())->method('save');
        $this->eventDispatcher->expects(self::once())->method('dispatch')
            ->with(self::isInstanceOf(OrderCreatedEvent::class));

        $result = $this->useCase->execute($this->makeDTO());

        self::assertInstanceOf(Order::class, $result);
        self::assertCount(1, $result->getOrderLines());
    }

    /**
     * @throws Exception
     */
    public function testExecuteSavesProductAfterStockDecrease(): void
    {
        $product = $this->makeProduct();
        $this->clientRepository->method('findById')->willReturn($this->makeClient());
        $this->productRepository->method('findById')->willReturn($product);

        $this->stockService->expects(self::once())
            ->method('decreaseStock')
            ->with($product, 2);

        $this->productRepository->expects(self::once())->method('save')->with($product);

        $this->useCase->execute($this->makeDTO());
    }

    /**
     * @throws Exception
     */
    public function testExecuteDispatchesOrderCreatedEventWithCorrectData(): void
    {
        $this->clientRepository->method('findById')->willReturn($this->makeClient());
        $this->productRepository->method('findById')->willReturn($this->makeProduct(10.0));

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::callback(function (OrderCreatedEvent $event): bool {
                return $event->clientId === 'client-001'
                    && $event->totalAmount === 20.0; // 2 × 10.00
            }));

        $this->useCase->execute($this->makeDTO());
    }

    /**
     * @throws Exception
     */
    public function testExecuteHandlesMultipleOrderLines(): void
    {
        $this->clientRepository->method('findById')->willReturn($this->makeClient());

        $product1 = $this->makeProduct(5.0);
        $product2 = $this->createMock(Product::class);
        $product2->method('getId')->willReturn('product-002');
        $product2->method('getName')->willReturn('Gadget');
        $product2->method('getReference')->willReturn('REF-002');
        $product2->method('getPrice')->willReturn(Money::of(15.0));

        $this->productRepository->method('findById')
            ->willReturnMap([
                ['product-001', $product1],
                ['product-002', $product2],
            ]);

        $dto = new CreateOrderDTO('client-001', [
            new OrderLineDTO('product-001', 2),
            new OrderLineDTO('product-002', 1),
        ]);

        $result = $this->useCase->execute($dto);

        self::assertCount(2, $result->getOrderLines());
        self::assertSame(25.0, $result->getTotalAmount()->amount()); // 10 + 15
    }

    // ------------------------------------------------------------------ client not found

    public function testExecuteThrowsClientNotFoundExceptionWhenClientMissing(): void
    {
        $this->clientRepository->method('findById')->willReturn(null);

        $this->orderRepository->expects(self::never())->method('save');
        $this->eventDispatcher->expects(self::never())->method('dispatch');

        $this->expectException(ClientNotFoundException::class);
        $this->useCase->execute($this->makeDTO());
    }

    // ------------------------------------------------------------------ product not found

    /**
     * @throws Exception
     */
    public function testExecuteThrowsProductNotFoundExceptionWhenProductMissing(): void
    {
        $this->clientRepository->method('findById')->willReturn($this->makeClient());
        $this->productRepository->method('findById')->willReturn(null);

        $this->orderRepository->expects(self::never())->method('save');
        $this->eventDispatcher->expects(self::never())->method('dispatch');

        $this->expectException(ProductNotFoundException::class);
        $this->useCase->execute($this->makeDTO());
    }
}
