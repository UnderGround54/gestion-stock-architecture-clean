<?php

namespace App\Application\UseCase\Client;

use App\Application\DTO\Request\CreateClientDTO;
use App\Domain\Exception\ClientAlreadyExistsException;
use App\Domain\Model\Entity\Client;
use App\Domain\Model\Repository\ClientRepositoryInterface;

final readonly class CreateClientUseCase
{
    public function __construct(
        private ClientRepositoryInterface $repository,
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

        $client = new Client(
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
