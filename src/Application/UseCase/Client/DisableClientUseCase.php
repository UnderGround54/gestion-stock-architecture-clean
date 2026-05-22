<?php

namespace App\Application\UseCase\Client;

use App\Domain\Model\Entity\Client;
use App\Domain\Model\Repository\ClientRepositoryInterface;

final readonly class DisableClientUseCase
{
    public function __construct(
        private GetClientUseCase          $getClient,
        private ClientRepositoryInterface $clientRepository,
    ) {}

    public function execute(string $id): Client
    {
        $client = $this->getClient->execute($id); // lance ClientNotFoundException si absent

        $client->disable();
        $this->clientRepository->save($client);

        return $client;
    }
}
