<?php

namespace App\Application\UseCase\Invoice;

use App\Domain\Exception\InvoiceNotFoundException;
use App\Domain\Model\Repository\InvoiceRepositoryInterface;

readonly class GetInvoiceUseCase
{

    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository
    ){}

    public function execute(string $id)
    {
        $invoice = $this->invoiceRepository->findById($id);

        if ($invoice === null) {
            throw new InvoiceNotFoundException("Facture introuvable avec l'ID : {$id}");
        }

        return $invoice;
    }
}
