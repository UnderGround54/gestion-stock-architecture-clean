<?php

namespace App\Presentation\DTO\Request;
use Symfony\Component\Validator\Constraints as Assert;


final readonly class GenerateInvoiceDTO
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\Positive(message: "Le taux de taxe doit être positif.")]
        #[Assert\LessThanOrEqual(
            value: 100,
            message: "Le taux de taxe ne peut pas dépasser 100%."
        )]
        public float $taxRate = 20.0,
    ) {}
}
