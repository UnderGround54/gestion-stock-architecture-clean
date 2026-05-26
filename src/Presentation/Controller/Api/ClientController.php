<?php

namespace App\Presentation\Controller\Api;

use App\Application\DTO\Request\CreateClientDTO;
use App\Application\DTO\Request\PaginationDTO;
use App\Application\UseCase\Client\CreateClientUseCase;
use App\Application\UseCase\Client\DisableClientUseCase;
use App\Application\UseCase\Client\GetClientUseCase;
use App\Application\UseCase\Client\ListClientsUseCase;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use App\Presentation\Response\ApiResponse;
use App\Presentation\Transformer\ResourceTransformer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/clients', name: 'api_clients_')]
final class ClientController extends AbstractController
{
    public function __construct(
        private readonly ListClientsUseCase   $listClients,
        private readonly CreateClientUseCase  $createClient,
        private readonly GetClientUseCase     $getClient,
        private readonly DisableClientUseCase $disableClient,
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $pagination = new PaginationDTO(
            page:  max(1, (int) $request->query->get('page', 1)),
            limit: min(100, max(1, (int) $request->query->get('limit', 10)))
        );
        $result = $this->listClients->execute($pagination);

        return ApiResponse::paginated(
            items:   array_map(ResourceTransformer::client(...), $result['items']),
            total:   $result['total'],
            page:    $result['page'],
            limit:   $result['limit'],
            message: 'Liste des clients récupérée.'
        );
    }

    #[Route('/{id}', name: 'detail', methods: ['GET'])]
    public function detail(string $id): JsonResponse
    {
        $client = $this->getClient->execute($id);

        return ApiResponse::success(
            ResourceTransformer::client($client),
            'Client récupéré.'
        );
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateClientDTO $dto): JsonResponse
    {
        $client = $this->createClient->execute($dto);

        return ApiResponse::created(
            ResourceTransformer::client($client),
            'Client créé avec succès.'
        );
    }

    #[Route('/{id}', name: 'disable', methods: ['DELETE'])]
    public function disable(string $id): JsonResponse
    {
        $this->disableClient->execute($id);

        return ApiResponse::success(null, 'Client désactivé avec succès.');
    }
}
