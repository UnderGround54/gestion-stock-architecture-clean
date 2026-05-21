<?php

namespace App\Application\DTO\Request;
use Symfony\Component\Validator\Constraints as Assert;


final readonly class PaginationDTO
{
    public function __construct(
        #[Assert\Positive]
        public int $page = 1,

        #[Assert\Range(min: 1, max: 100)]
        public int $limit = 10
    ) {}

    public function getOffset(): int
    {
        return ($this->page - 1) * $this->limit;
    }
}

