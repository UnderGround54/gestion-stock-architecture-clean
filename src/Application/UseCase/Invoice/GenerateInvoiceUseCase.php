<?php

namespace App\Application\UseCase\Invoice;

use App\Application\Factory\InvoiceFactory;
use App\Domain\Enum\OrderStatus;
use App\Domain\Event\InvoiceCreatedEvent;
use App\Domain\Exception\InvoiceException;
use App\Domain\Exception\OrderNotFoundException;
use App\Domain\Model\Entity\Invoice;
use App\Domain\Model\Repository\InvoiceRepositoryInterface;
use App\Domain\Model\Repository\OrderRepositoryInterface;
use App\Domain\Port\EventDispatcherInterface;
use App\Presentation\DTO\Request\GenerateInvoiceDTO;

final readonly class GenerateInvoiceUseCase
{
    public function __construct(
        private OrderRepositoryInterface   $orderRepository,
        private InvoiceRepositoryInterface $invoiceRepository,
        private EventDispatcherInterface   $eventDispatcher,
        private InvoiceFactory             $invoiceFactory
    ) {}

    public function execute(string $orderId, GenerateInvoiceDTO $dto): Invoice
    {
        $order = $this->orderRepository->findById($orderId);

        if ($order === null) {
            throw new OrderNotFoundException(
                "Commande introuvable avec l'ID : {$orderId}"
            );
        }

        // Règle de gestion: on ne facture que les commandes confirmées
        if ($order->getStatus() !== OrderStatus::CONFIRMED) {
            throw new InvoiceException(
                "Impossible de générer une facture pour une commande en statut : "
                . $order->getStatus()->label()
            );
        }

        // Règle de gestion: pas de double facturation
        $existingInvoice = $this->invoiceRepository->findByOrderId($orderId);
        if ($existingInvoice !== null) {
            throw new InvoiceException(
                "Une facture existe déjà pour la commande : {$order->getNumber()}"
            );
        }

        $invoice = $this->invoiceFactory->createFromOrder($order, $dto->taxRate);
        $this->invoiceRepository->save($invoice);

        $this->eventDispatcher->dispatch(new InvoiceCreatedEvent(
            invoiceId:          $invoice->getId(),
            orderId:            $invoice->getOrderId(),
            clientId:           $invoice->getClientId(),
            invoiceNumber:      $invoice->getNumber(),
            totalAmountInclTax: $invoice->getAmountInclTax()->amount()
        ));

        return $invoice;
    }
}
