<?php

namespace App\Application\UseCase\Client;

use App\Application\DTO\Request\PaginationDTO;
use App\Domain\Model\Repository\ClientRepositoryInterface;


final readonly class ListClientsUseCase
{
    public function __construct(
        private ClientRepositoryInterface $clientRepository
    ) {}

    public function execute(PaginationDTO $pagination): array
    {
        $clients = $this->clientRepository->findAll($pagination->page, $pagination->limit);
        $total    = $this->clientRepository->countAll();

        return [
            'items'       => $clients,
            'total'       => $total,
            'page'        => $pagination->page,
            'limit'       => $pagination->limit,
            'total_pages' => (int) ceil($total / $pagination->limit),
        ];
    }
}

