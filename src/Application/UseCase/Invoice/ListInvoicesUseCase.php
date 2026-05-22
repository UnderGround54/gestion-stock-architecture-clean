<?php

namespace App\Application\UseCase\Invoice;

use App\Application\DTO\Request\PaginationDTO;
use App\Domain\Model\Repository\InvoiceRepositoryInterface;


final readonly class ListInvoicesUseCase
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository
    ) {}

    public function execute(PaginationDTO $pagination): array
    {
        $invoices = $this->invoiceRepository->findAll($pagination->page, $pagination->limit);
        $total    = $this->invoiceRepository->countAll();

        return [
            'items'       => $invoices,
            'total'       => $total,
            'page'        => $pagination->page,
            'limit'       => $pagination->limit,
            'total_pages' => (int) ceil($total / $pagination->limit),
        ];
    }
}

