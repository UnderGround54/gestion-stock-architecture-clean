<?php

namespace App\Application\UseCase\Client;

use App\Domain\Exception\ClientNotFoundException;
use App\Domain\Model\Entity\Client;
use App\Domain\Model\Repository\ClientRepositoryInterface;

final readonly class DisableClientUseCase
{
    public function __construct(
        private ClientRepositoryInterface $clientRepository,
    ) {}

    public function execute(string $id): Client
    {
        $client = $this->clientRepository->findById($id);

        if ($client === null) {
            throw new ClientNotFoundException("Client introuvable avec l'ID : {$id}");
        }

        $client->disable();
        $this->clientRepository->save($client);

        return $client;
    }
}
