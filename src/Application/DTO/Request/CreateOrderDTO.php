<?php

namespace App\Application\DTO\Request;
use Symfony\Component\Validator\Constraints as Assert;


final readonly class CreateOrderDTO
{
    public function __construct(
        #[Assert\NotBlank(message: "L'ID du client est obligatoire.")]
        public string $clientId,

        /**
         * @var OrderLineDTO[]
         */
        #[Assert\NotNull]
        #[Assert\Count(min: 1, minMessage: "La commande doit contenir au moins une ligne.")]
        #[Assert\Valid]
        public array  $orderLines,

        public string $customerNote = ''
    ) {}
}

