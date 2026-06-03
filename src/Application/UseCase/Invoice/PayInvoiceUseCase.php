<?php

namespace App\Application\UseCase\Invoice;

use App\Domain\Exception\InvoiceNotFoundException;
use App\Domain\Model\Entity\Invoice;
use App\Domain\Model\Repository\InvoiceRepositoryInterface;

readonly class PayInvoiceUseCase
{

    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository
    ){}

    public function execute(string $id) : Invoice
    {
        $invoice = $this->invoiceRepository->findById($id);

        if ($invoice === null) {
            throw new InvoiceNotFoundException("Facture introuvable avec l'ID : {$id}");
        }

        $invoice->markAsPaid();
        $this->invoiceRepository->save($invoice);

        return $invoice;
    }
}
