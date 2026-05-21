<?php

namespace App\Application\DTO\Request;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateProductDTO
{
    public function __construct(
        #[Assert\NotBlank(message: "Le nom est obligatoire.")]
        #[Assert\Length(min: 2, max: 255, minMessage: "Le nom doit avoir au moins 2 caractères.")]
        public string $name,

        #[Assert\NotBlank(message: "La référence est obligatoire.")]
        #[Assert\Length(min: 2, max: 50)]
        public string $reference,

        #[Assert\NotBlank(message: "La description est obligatoire.")]
        public string $description,

        #[Assert\NotNull(message: "Le prix est obligatoire.")]
        #[Assert\Positive(message: "Le prix doit être positif.")]
        public float  $price,

        #[Assert\NotNull]
        #[Assert\PositiveOrZero(message: "La quantité ne peut pas être négative.")]
        public int    $stockQuantity,

        #[Assert\PositiveOrZero]
        public int    $minimumStock = 5,

        public string $currency = 'MGA'
    ) {}
}
