<?php

namespace App\Presentation\DTO\Request;
use App\Domain\Enum\StockOperation;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateStockDTO
{
    public function __construct(
        #[Assert\NotBlank]
        public string $productId,

        #[Assert\NotNull]
        #[Assert\Positive(message: "La quantité doit être positive.")]
        public int    $quantity,

        #[Assert\NotBlank]
        #[Assert\Choice(
            choices: ['increase', 'decrease'],
            message: "L'opération doit être 'augmenter' ou 'diminuer'."
        )]
        public StockOperation $operation,
    ) {}
}
