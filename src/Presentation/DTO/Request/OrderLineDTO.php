<?php

namespace App\Presentation\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class OrderLineDTO
{
    public function __construct(
        #[Assert\NotBlank(message: "L'ID du produit est obligatoire.")]
        public string $productId,

        #[Assert\NotNull]
        #[Assert\Positive(message: "La quantité doit être positive.")]
        public int    $quantity
    )
    { }
}
