<?php

namespace App\Presentation\Controller\Api;

use App\Application\DTO\Request\CreateOrderDTO;
use App\Application\DTO\Request\OrderLineDTO;
use App\Application\DTO\Request\PaginationDTO;
use App\Application\UseCase\Order\ConfirmOrderUseCase;
use App\Application\UseCase\Order\CreateOrderUseCase;
use App\Application\UseCase\Order\GetOrderUseCase;
use App\Application\UseCase\Order\ListOrdersUseCase;
use App\Presentation\Response\ApiResponse;
use App\Presentation\Transformer\ResourceTransformer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/orders', name: 'api_orders_')]
final class OrderController extends AbstractController
{
    public function __construct(
        private readonly CreateOrderUseCase $createOrder,
        private readonly ConfirmOrderUseCase $confirmOrder,
        private readonly ListOrdersUseCase  $listOrder,
        private readonly GetOrderUseCase    $getOrder,
        private readonly ValidatorInterface $validator
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $pagination = new PaginationDTO(
            page:  max(1, (int) $request->query->get('page', 1)),
            limit: min(100, max(1, (int) $request->query->get('limit', 10)))
        );

        $result = $this->listOrder->execute($pagination);

        return ApiResponse::paginated(
            items:   ResourceTransformer::collection($result['items'], 'order'),
            total:   $result['total'],
            page:    $result['page'],
            limit:   $result['limit'],
            message: 'Liste des commandes récupérée.'
        );
    }

    #[Route('/{id}', name: 'detail', methods: ['GET'])]
    public function detail(string $id): JsonResponse
    {
        $order = $this->getOrder->execute($id);

        return ApiResponse::success(
            ResourceTransformer::order($order),
            'Commande récupérée.'
        );
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true) ?? [];

        $linesDTO = array_map(
            fn(array $l) => new OrderLineDTO(
                productId: $l['product_id'] ?? '',
                quantity:  (int) ($l['quantity'] ?? 0)
            ),
            $body['order_lines'] ?? []
        );

        $dto = new CreateOrderDTO(
            clientId:     $body['client_id'] ?? '',
            orderLines:   $linesDTO,
            customerNote: $body['customer_note'] ?? ''
        );

        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $v) {
                $errors[$v->getPropertyPath()][] = $v->getMessage();
            }
            return ApiResponse::error('Données invalides.', 422, $errors);
        }

        $order = $this->createOrder->execute($dto);

        return ApiResponse::created(
            ResourceTransformer::order($order),
            'Commande créée avec succès.'
        );
    }

    #[Route('/{id}/confirm', name: 'confirm', methods: ['PATCH'])]
    public function confirm(string $id): JsonResponse
    {
        $order = $this->confirmOrder->execute($id);

        return ApiResponse::success(
            ResourceTransformer::order($order),
            'Commande confirmée avec succès.'
        );
    }
}
