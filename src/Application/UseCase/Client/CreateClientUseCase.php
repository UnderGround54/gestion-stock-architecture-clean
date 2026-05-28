<?php

namespace App\Application\UseCase\Client;

use App\Domain\Exception\ClientAlreadyExistsException;
use App\Domain\Model\Entity\Client;
use App\Domain\Model\Repository\ClientRepositoryInterface;
use App\Domain\Port\IdGeneratorInterface;
use App\Presentation\DTO\Request\CreateClientDTO;

final readonly class CreateClientUseCase
{
    public function __construct(
        private ClientRepositoryInterface $repository,
        private IdGeneratorInterface $idGenerator
    ) {}

    /**
     * @throws ClientAlreadyExistsException
     */
    public function execute(CreateClientDTO $dto): Client
    {
        if ($this->repository->existsByEmail($dto->email)) {
            throw new ClientAlreadyExistsException(
                "Un client avec l'email '{$dto->email}' existe déjà."
            );
        }

        $id = $this->idGenerator->generate();

        $client = new Client(
            $id,
            $dto->lastName,
            $dto->firstName,
            $dto->email,
            $dto->phone,
            $dto->address
        );

        $this->repository->save($client);

        return $client;
    }
}
