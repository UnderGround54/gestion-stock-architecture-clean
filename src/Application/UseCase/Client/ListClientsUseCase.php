<?php

namespace App\Application\UseCase\Client;

use App\Domain\Model\Repository\ClientRepositoryInterface;
use App\Presentation\DTO\Request\PaginationDTO;


final readonly class ListClientsUseCase
{
    public function __construct(
        private ClientRepositoryInterface $clientRepository
    ) {}

    public function execute(PaginationDTO $pagination): array
    {
        $clients = $this->clientRepository->findAll(
            $pagination->page,
            $pagination->limit,
            $pagination->getSort(),
            $pagination->filters
        );
        $total    = $this->clientRepository->countAll($pagination->filters);

        return [
            'items'       => $clients,
            'total'       => $total,
            'page'        => $pagination->page,
            'limit'       => $pagination->limit,
            'total_pages' => (int) ceil($total / $pagination->limit),
        ];
    }
}