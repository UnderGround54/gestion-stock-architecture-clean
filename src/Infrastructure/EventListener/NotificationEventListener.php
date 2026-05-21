<?php

namespace App\Infrastructure\EventListener;

use App\Domain\Event\InsufficientStockEvent;
use App\Domain\Event\InvoiceCreatedEvent;
use App\Domain\Event\OrderConfirmedEvent;
use App\Domain\Event\OrderCreatedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: OrderCreatedEvent::class)]
#[AsEventListener(event: OrderConfirmedEvent::class)]
#[AsEventListener(event: InsufficientStockEvent::class)]
#[AsEventListener(event: InvoiceCreatedEvent::class)]
final readonly class NotificationEventListener
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function __invoke(
        OrderCreatedEvent|OrderConfirmedEvent|InsufficientStockEvent|InvoiceCreatedEvent $event
    ): void {
        match (true) {
            $event instanceof OrderCreatedEvent      => $this->onOrderCreated($event),
            $event instanceof OrderConfirmedEvent    => $this->onOrderConfirmed($event),
            $event instanceof InsufficientStockEvent => $this->onInsufficientStock($event),
            $event instanceof InvoiceCreatedEvent    => $this->onInvoiceCreated($event),
        };
    }

    private function onOrderCreated(OrderCreatedEvent $event): void
    {
        $this->logger->info('[EVENT] Commande créée', [
            'order_id' => $event->orderId,
            'number'   => $event->orderNumber,
            'amount'   => $event->totalAmount,
        ]);
        // Ici : envoyer email de confirmation, notifier ERP, etc.
    }

    private function onOrderConfirmed(OrderConfirmedEvent $event): void
    {
        $this->logger->info('[EVENT] Commande confirmée', [
            'order_id' => $event->orderId,
            'number'   => $event->orderNumber,
        ]);
        // Ici : déclencher la préparation logistique
    }

    private function onInsufficientStock(InsufficientStockEvent $event): void
    {
        $this->logger->warning('[EVENT] Stock sous le minimum', [
            'product_id'       => $event->productId,
            'product'          => $event->productName,
            'current_quantity' => $event->currentQuantity,
            'minimum_stock'    => $event->minimumStock,
        ]);
        // Ici : alerter le gestionnaire de stock, déclencher réapprovisionnement
    }

    private function onInvoiceCreated(InvoiceCreatedEvent $event): void
    {
        $this->logger->info('[EVENT] Facture générée', [
            'invoice_id'          => $event->invoiceId,
            'number'              => $event->invoiceNumber,
            'total_amount_incl_tax' => $event->totalAmountInclTax,
        ]);
        // Ici : envoyer la facture par email au client
    }
}
