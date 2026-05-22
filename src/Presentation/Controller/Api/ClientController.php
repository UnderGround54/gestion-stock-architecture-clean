<?php

namespace App\Presentation\Controller\Api;

use App\Application\DTO\Request\CreateClientDTO;
use App\Application\DTO\Request\PaginationDTO;
use App\Application\UseCase\Client\CreateClientUseCase;
use App\Application\UseCase\Client\DisableClientUseCase;
use App\Application\UseCase\Client\GetClientUseCase;
use App\Application\UseCase\Client\ListClientsUseCase;
use App\Presentation\Http\RequestParser;
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
        private readonly RequestParser        $parser,
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
            items:   ResourceTransformer::collection($result['items'], 'client'),
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
    public function create(Request $request): JsonResponse
    {
        $body = $this->parser->body($request);

        $dto = new CreateClientDTO(
            lastName:  $body['last_name']  ?? '',
            firstName: $body['first_name'] ?? '',
            email:     $body['email']      ?? '',
            phone:     $body['phone']      ?? '',
            address:   $body['address']    ?? ''
        );

        if ($error = $this->parser->validate($dto)) {
            return $error;
        }

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
