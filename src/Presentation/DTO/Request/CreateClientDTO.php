<?php

namespace App\Presentation\DTO\Request;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateClientDTO
{
    public function __construct(
        #[Assert\NotBlank(message: "Le nom est obligatoire.")]
        #[Assert\Length(min: 2, max: 100)]
        public string $lastName,

        #[Assert\NotBlank(message: "Le prénom est obligatoire.")]
        #[Assert\Length(min: 2, max: 100)]
        public string $firstName,

        #[Assert\NotBlank(message: "L'email est obligatoire.")]
        #[Assert\Email(message: "L'email '{{ value }}' est invalide.")]
        public string $email,

        #[Assert\NotBlank(message: "Le téléphone est obligatoire.")]
        #[Assert\Length(
            min: 8,
            max: 12,
            minMessage: 'Le numéro téléphone doit être entre 8 et 20 caractères',
            maxMessage: 'Le numéro téléphone doit être entre 8 et 20 caractères'
        )]
        public string $phone,

        #[Assert\NotBlank(message: "L'adresse est obligatoire.")]
        public string $address
    ) {}
}
