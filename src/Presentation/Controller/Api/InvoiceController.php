<?php

namespace App\Presentation\Controller\Api;

use App\Application\DTO\Request\PaginationDTO;
use App\Application\UseCase\Invoice\GenerateInvoiceUseCase;
use App\Domain\Exception\InvoiceNotFoundException;
use App\Domain\Model\Repository\InvoiceRepositoryInterface;
use App\Presentation\Response\ApiResponse;
use App\Presentation\Transformer\ResourceTransformer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/invoices', name: 'api_invoices_')]
final class InvoiceController extends AbstractController
{
    public function __construct(
        private readonly GenerateInvoiceUseCase $generateInvoice,
        private readonly InvoiceRepositoryInterface $invoiceRepository
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $pagination = new PaginationDTO(
            page:  max(1, (int) $request->query->get('page', 1)),
            limit: min(100, max(1, (int) $request->query->get('limit', 10)))
        );

        $invoices = $this->invoiceRepository->findAll($pagination->page, $pagination->limit);
        $total    = $this->invoiceRepository->countAll();

        return ApiResponse::paginated(
            items:   ResourceTransformer::collection($invoices, 'invoice'),
            total:   $total,
            page:    $pagination->page,
            limit:   $pagination->limit,
            message: 'Liste des factures récupérée.'
        );
    }

    #[Route('/{id}', name: 'detail', methods: ['GET'])]
    public function detail(string $id): JsonResponse
    {
        $invoice = $this->invoiceRepository->findById($id);

        if ($invoice === null) {
            throw new InvoiceNotFoundException("Facture introuvable avec l'ID : {$id}");
        }

        return ApiResponse::success(
            ResourceTransformer::invoice($invoice),
            'Facture récupérée.'
        );
    }

    #[Route('/orders/{orderId}/generate', name: 'generate', methods: ['POST'])]
    public function generate(string $orderId, Request $request): JsonResponse
    {
        $body    = json_decode($request->getContent(), true) ?? [];
        $taxRate = (float) ($body['tax_rate'] ?? 20.0);

        $invoice = $this->generateInvoice->execute($orderId, $taxRate);

        return ApiResponse::created(
            ResourceTransformer::invoice($invoice),
            'Facture générée avec succès.'
        );
    }

    #[Route('/{id}/pay', name: 'pay', methods: ['PATCH'])]
    public function pay(string $id): JsonResponse
    {
        $invoice = $this->invoiceRepository->findById($id);

        if ($invoice === null) {
            throw new InvoiceNotFoundException("Facture introuvable avec l'ID : {$id}");
        }

        $invoice->markAsPaid();
        $this->invoiceRepository->save($invoice);

        return ApiResponse::success(
            ResourceTransformer::invoice($invoice),
            'Facture marquée comme payée.'
        );
    }
}
