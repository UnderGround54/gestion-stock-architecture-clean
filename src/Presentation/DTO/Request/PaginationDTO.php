<?php

namespace App\Presentation\DTO\Request;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * DTO pour la pagination avancée avec tri et filtrage
 */
final readonly class PaginationDTO
{
    public function __construct(
        #[Assert\Positive]
        public int $page = 1,

        #[Assert\Range(min: 1, max: 100)]
        public int $limit = 10,

        #[Assert\Regex(pattern: '/^[a-zA-Z0-9_]+:(ASC|DESC)$/i', match: true, message: "Le format du tri doit être 'champ:direction' (ex: created_at:DESC)")]
        public ?string $sort = null,

        // Filtres simples sous forme de tableau associatif [champ => valeur]
        // Exemple: ['status' => 'CONFIRMED', 'clientId' => '123']
        public array $filters = []
    ) {}

    public function getOffset(): int
    {
        return ($this->page - 1) * $this->limit;
    }

    /**
     * Récupère le champ de tri et la direction
     * @return array{string, string}|null Retourne [champ, direction] ou null si aucun tri spécifié
     */
    public function getSort(): ?array
    {
        if ($this->sort === null) {
            return null;
        }

        $parts = explode(':', $this->sort);
        if (count($parts) !== 2) {
            return null;
        }

        return [$parts[0], strtoupper($parts[1])];
    }
}