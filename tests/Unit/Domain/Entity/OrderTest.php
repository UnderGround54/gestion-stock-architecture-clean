<?php

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Enum\OrderStatus;
use App\Domain\Exception\OrderException;
use App\Domain\Model\Entity\Order;
use App\Domain\Model\Entity\OrderLine;
use App\Domain\ValueObject\Money;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Order::class)]
final class OrderTest extends TestCase
{
    private const CLIENT_ID = 'client-uuid-001';

    // ------------------------------------------------------------------ helpers

    private function makeOrder(string $note = ''): Order
    {
        return new Order('order-uuid-001', self::CLIENT_ID, $note);
    }

    private function makeLine(float $unitPrice = 10.0, int $quantity = 2): OrderLine
    {
        return new OrderLine(
            id:               'line-uuid-001',
            productId:        'prod-uuid-001',
            productName:      'Widget',
            productReference: 'REF-001',
            quantity:         $quantity,
            unitPrice:        Money::of($unitPrice)
        );
    }

    // ------------------------------------------------------------------ constructor

    public function testConstructorSetsPendingStatus(): void
    {
        $order = $this->makeOrder();
        self::assertSame(OrderStatus::PENDING, $order->getStatus());
    }

    public function testConstructorSetsZeroTotal(): void
    {
        $order = $this->makeOrder();
        self::assertSame(0.0, $order->getTotalAmount()->amount());
    }

    public function testConstructorSetsClientId(): void
    {
        $order = $this->makeOrder();
        self::assertSame(self::CLIENT_ID, $order->getClientId());
    }

    public function testConstructorSetsCustomerNote(): void
    {
        $order = $this->makeOrder('Please deliver before noon.');
        self::assertSame('Please deliver before noon.', $order->getCustomerNote());
    }

    public function testConstructorGeneratesOrderNumber(): void
    {
        $order = $this->makeOrder();
        self::assertStringStartsWith('CMD-', $order->getNumber());
    }

    public function testConstructorSetsTimestamps(): void
    {
        $before = new \DateTimeImmutable();
        $order  = $this->makeOrder();
        $after  = new \DateTimeImmutable();

        self::assertGreaterThanOrEqual($before, $order->getCreatedAt());
        self::assertLessThanOrEqual($after,     $order->getCreatedAt());
    }

    // ------------------------------------------------------------------ addLine()

    public function testAddLineAppendsLineToOrder(): void
    {
        $order = $this->makeOrder();
        $order->addLine($this->makeLine());
        self::assertCount(1, $order->getOrderLines());
    }

    public function testAddLineRecalculatesTotal(): void
    {
        $order = $this->makeOrder();
        $order->addLine($this->makeLine(10.0, 3));   // sub-total = 30.00
        self::assertSame(30.0, $order->getTotalAmount()->amount());
    }

    public function testAddMultipleLinesAccumulatesTotal(): void
    {
        $order = $this->makeOrder();
        $order->addLine($this->makeLine(10.0, 2));   // 20
        $order->addLine($this->makeLine(5.0,  4));   // 20
        self::assertSame(40.0, $order->getTotalAmount()->amount());
    }

    public function testAddLineThrowsWhenNotPending(): void
    {
        $order = $this->makeOrder();
        $order->addLine($this->makeLine());
        $order->confirm();                            // status → CONFIRMED

        $this->expectException(OrderException::class);
        $order->addLine($this->makeLine());
    }

    public function testAddLineUpdatesUpdatedAt(): void
    {
        $order  = $this->makeOrder();
        $before = $order->getUpdatedAt();

        // ensure a measurable time difference
        usleep(1000);
        $order->addLine($this->makeLine());

        self::assertGreaterThanOrEqual($before, $order->getUpdatedAt());
    }

    // ------------------------------------------------------------------ confirm()

    public function testConfirmChangesStatusToConfirmed(): void
    {
        $order = $this->makeOrder();
        $order->addLine($this->makeLine());
        $order->confirm();
        self::assertSame(OrderStatus::CONFIRMED, $order->getStatus());
    }

    public function testConfirmThrowsWhenNoLines(): void
    {
        $order = $this->makeOrder();
        $this->expectException(OrderException::class);
        $order->confirm();
    }

    public function testConfirmThrowsWhenAlreadyConfirmed(): void
    {
        $order = $this->makeOrder();
        $order->addLine($this->makeLine());
        $order->confirm();

        $this->expectException(OrderException::class);
        $order->confirm();
    }

    public function testConfirmThrowsWhenCancelled(): void
    {
        $order = $this->makeOrder();
        $order->addLine($this->makeLine());
        $order->cancel();

        $this->expectException(OrderException::class);
        $order->confirm();
    }

    // ------------------------------------------------------------------ ship()

    public function testShipChangesStatusToShipped(): void
    {
        $order = $this->makeOrder();
        $order->addLine($this->makeLine());
        $order->confirm();
        $order->ship();
        self::assertSame(OrderStatus::SHIPPED, $order->getStatus());
    }

    public function testShipThrowsWhenPending(): void
    {
        $order = $this->makeOrder();
        $this->expectException(OrderException::class);
        $order->ship();
    }

    public function testShipThrowsWhenAlreadyShipped(): void
    {
        $order = $this->makeOrder();
        $order->addLine($this->makeLine());
        $order->confirm();
        $order->ship();

        $this->expectException(OrderException::class);
        $order->ship();
    }

    // ------------------------------------------------------------------ deliver()

    public function testDeliverChangesStatusToDelivered(): void
    {
        $order = $this->makeOrder();
        $order->addLine($this->makeLine());
        $order->confirm();
        $order->ship();
        $order->deliver();
        self::assertSame(OrderStatus::DELIVERED, $order->getStatus());
    }

    public function testDeliverThrowsWhenNotShipped(): void
    {
        $order = $this->makeOrder();
        $order->addLine($this->makeLine());
        $order->confirm();

        $this->expectException(OrderException::class);
        $order->deliver();
    }

    // ------------------------------------------------------------------ cancel()

    public function testCancelFromPendingChangesStatusToCancelled(): void
    {
        $order = $this->makeOrder();
        $order->cancel();
        self::assertSame(OrderStatus::CANCELLED, $order->getStatus());
    }

    public function testCancelFromConfirmedChangesStatusToCancelled(): void
    {
        $order = $this->makeOrder();
        $order->addLine($this->makeLine());
        $order->confirm();
        $order->cancel();
        self::assertSame(OrderStatus::CANCELLED, $order->getStatus());
    }

    public function testCancelThrowsWhenShipped(): void
    {
        $order = $this->makeOrder();
        $order->addLine($this->makeLine());
        $order->confirm();
        $order->ship();

        $this->expectException(OrderException::class);
        $order->cancel();
    }

    public function testCancelThrowsWhenDelivered(): void
    {
        $order = $this->makeOrder();
        $order->addLine($this->makeLine());
        $order->confirm();
        $order->ship();
        $order->deliver();

        $this->expectException(OrderException::class);
        $order->cancel();
    }
}
