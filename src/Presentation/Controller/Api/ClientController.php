<?php

namespace App\Presentation\Controller\Api;

use App\Application\DTO\Request\CreateClientDTO;
use App\Application\DTO\Request\PaginationDTO;
use App\Domain\Exception\ClientNotFoundException;
use App\Domain\Model\Entity\Client;
use App\Domain\Model\Repository\ClientRepositoryInterface;
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
        private readonly ClientRepositoryInterface $clientRepository,
        private readonly ValidatorInterface $validator
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $pagination = new PaginationDTO(
            page:  max(1, (int) $request->query->get('page', 1)),
            limit: min(100, max(1, (int) $request->query->get('limit', 10)))
        );
        $clients = $this->clientRepository->findAll($pagination->page, $pagination->limit);
        $total   = $this->clientRepository->countAll();

        return ApiResponse::paginated(
            items:   ResourceTransformer::collection($clients, 'client'),
            total:   $total,
            page:    $pagination->page,
            limit:   $pagination->limit,
            message: 'Liste des clients récupérée.'
        );
    }

    #[Route('/{id}', name: 'detail', methods: ['GET'])]
    public function detail(string $id): JsonResponse
    {
        $client = $this->clientRepository->findById($id);

        if ($client === null) {
            throw new ClientNotFoundException("ClientOrm introuvable avec l'ID : {$id}");
        }

        return ApiResponse::success(
            ResourceTransformer::client($client),
            'ClientOrm récupéré.'
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

        // Règle de gestion: email unique
        if ($this->clientRepository->existsByEmail($dto->email)) {
            return ApiResponse::error(
                "Un client avec l'email '{$dto->email}' existe déjà.",
                409
            );
        }

        $client = new Client(
            $dto->lastName,
            $dto->firstName,
            $dto->email,
            $dto->phone,
            $dto->address
        );

        $this->clientRepository->save($client);

        return ApiResponse::created(
            ResourceTransformer::client($client),
            'ClientOrm créé avec succès.'
        );
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $client = $this->clientRepository->findById($id);

        if ($client === null) {
            throw new ClientNotFoundException("ClientOrm introuvable avec l'ID : {$id}");
        }

        $client->disable();
        $this->clientRepository->save($client);

        return ApiResponse::success(null, 'ClientOrm désactivé avec succès.');
    }
}
