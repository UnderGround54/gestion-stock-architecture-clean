<?php

namespace App\Presentation\Controller\Api;

use App\Application\UseCase\Invoice\GenerateInvoiceUseCase;
use App\Application\UseCase\Invoice\GetInvoiceUseCase;
use App\Application\UseCase\Invoice\ListInvoicesUseCase;
use App\Application\UseCase\Invoice\PayInvoiceUseCase;
use App\Presentation\DTO\Request\GenerateInvoiceDTO;
use App\Presentation\DTO\Request\PaginationDTO;
use App\Presentation\Http\RequestParser;
use App\Presentation\Response\ApiResponse;
use App\Presentation\Transformer\ResourceTransformer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/invoices', name: 'api_invoices_')]
final class InvoiceController extends AbstractController
{
    public function __construct(
        private readonly GenerateInvoiceUseCase $generateInvoice,
        private readonly GetInvoiceUseCase    $getInvoice,
        private readonly ListInvoicesUseCase  $listInvoice,
        private readonly PayInvoiceUseCase    $payInvoice,
        private readonly RequestParser        $parser,
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $pagination = new PaginationDTO(
            page: (int) $request->query->get('page', 1),
            limit: (int) $request->query->get('limit', 10),
            sort: $request->query->get('sort', null),
            filters: json_decode($request->query->get('filters', '{}'), true) ?? []
        );

        $result = $this->listInvoice->execute($pagination);

        return ApiResponse::paginated(
            items:   array_map(ResourceTransformer::invoice(...), $result['items']),
            total:   $result['total'],
            page:    $result['page'],
            limit:   $result['limit'],
            message: 'Liste des factures récupérée.'
        );
    }

    #[Route('/{id}', name: 'detail', methods: ['GET'])]
    public function detail(string $id): JsonResponse
    {
        $invoice = $this->getInvoice->execute($id);

        return ApiResponse::success(
            ResourceTransformer::invoice($invoice),
            'Facture récupérée.'
        );
    }

    #[Route('/orders/{orderId}/generate', name: 'generate', methods: ['POST'])]
    public function generate(string $orderId, #[MapRequestPayload(validationGroups: ['Default'])] GenerateInvoiceDTO $dto): JsonResponse
    {
        $invoice = $this->generateInvoice->execute($orderId, $dto);

        return ApiResponse::created(
            ResourceTransformer::invoice($invoice),
            'Facture générée avec succès.'
        );
    }

    #[Route('/{id}/pay', name: 'pay', methods: ['PATCH'])]
    public function pay(string $id): JsonResponse
    {
        $invoice = $this->payInvoice->execute($id);

        return ApiResponse::success(
            ResourceTransformer::invoice($invoice),
            'Facture payée avec succès.'
        );
    }
}
