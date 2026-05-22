<?php

namespace App\Presentation\Controller\Api;

use App\Application\DTO\Request\CreateClientDTO;
use App\Application\DTO\Request\PaginationDTO;
use App\Application\UseCase\Client\CreateClientUseCase;
use App\Application\UseCase\Client\DisableClientUseCase;
use App\Application\UseCase\Client\GetClientUseCase;
use App\Application\UseCase\Client\ListClientsUseCase;
use App\Presentation\Response\ApiResponse;
use App\Presentation\Transformer\ResourceTransformer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/clients', name: 'api_clients_')]
final class ClientController extends AbstractController
{
    public function __construct(
        private readonly ListClientsUseCase   $listClients,
        private readonly CreateClientUseCase  $createClient,
        private readonly GetClientUseCase     $getClient,
        private readonly DisableClientUseCase $disableClient,
        private readonly ValidatorInterface   $validator
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
        $body = json_decode($request->getContent(), true) ?? [];

        $dto = new CreateClientDTO(
            lastName:  $body['last_name'] ?? '',
            firstName: $body['first_name'] ?? '',
            email:     $body['email'] ?? '',
            phone:     $body['phone'] ?? '',
            address:   $body['address'] ?? ''
        );

        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $v) {
                $errors[$v->getPropertyPath()][] = $v->getMessage();
            }
            return ApiResponse::error('Données invalides.', 422, $errors);
        }

        $client = $this->createClient->execute($dto);

        return ApiResponse::created(
            ResourceTransformer::client($client),
            'Client créé avec succès.'
        );
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $this->disableClient->execute($id);

        return ApiResponse::success(null, 'Client désactivé avec succès.');
    }
}
