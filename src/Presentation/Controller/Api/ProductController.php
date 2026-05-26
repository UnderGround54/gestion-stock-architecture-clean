<?php

namespace App\Presentation\Controller\Api;

use App\Application\DTO\Request\CreateProductDTO;
use App\Application\DTO\Request\PaginationDTO;
use App\Application\DTO\Request\UpdateStockDTO;
use App\Application\UseCase\Product\CreateProductUseCase;
use App\Application\UseCase\Product\GetProductUseCase;
use App\Application\UseCase\Product\ListProductsUseCase;
use App\Application\UseCase\Product\UpdateStockUseCase;
use App\Presentation\Response\ApiResponse;
use App\Presentation\Transformer\ResourceTransformer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use App\Presentation\Http\RequestParser;

#[Route('/api/v1/products', name: 'api_products_')]
final class ProductController extends AbstractController
{
    public function __construct(
        private readonly CreateProductUseCase $createProduct,
        private readonly ListProductsUseCase  $listProducts,
        private readonly UpdateStockUseCase   $updateStock,
        private readonly GetProductUseCase    $getProduct,
        private readonly RequestParser        $parser,
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $pagination = new PaginationDTO(
            page:  max(1, (int) $request->query->get('page', 1)),
            limit: min(100, max(1, (int) $request->query->get('limit', 10)))
        );

        $result = $this->listProducts->execute($pagination);

        return ApiResponse::paginated(
            items:   array_map(ResourceTransformer::product(...), $result['items']),
            total:   $result['total'],
            page:    $result['page'],
            limit:   $result['limit'],
            message: 'Liste des produits récupérée.'
        );
    }

    #[Route('/{id}', name: 'detail', methods: ['GET'])]
    public function detail(string $id): JsonResponse
    {
        $product = $this->getProduct->execute($id);

        return ApiResponse::success(
            ResourceTransformer::product($product),
            'Produit récupéré.'
        );
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateProductDTO $dto): JsonResponse
    {
        $product = $this->createProduct->execute($dto);

        return ApiResponse::created(
            ResourceTransformer::product($product),
            'Produit créé avec succès.'
        );
    }

    #[Route('/{id}/stock', name: 'update_stock', methods: ['PATCH'])]
    public function updateStock(string $id, Request $request): JsonResponse
    {
        $body = $this->parser->body($request);

        $dto = new UpdateStockDTO(
            productId:  $id,
            quantity:   (int) ($body['quantity']  ?? 0),
            operation:  $body['operation']        ?? ''
        );

        if ($error = $this->parser->validate($dto)) {
            return $error;
        }

        $product = $this->updateStock->execute($dto);

        return ApiResponse::success(
            ResourceTransformer::product($product),
            'Stock mis à jour avec succès.'
        );
    }
}
