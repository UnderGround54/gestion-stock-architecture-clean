<?php

namespace App\Presentation\Http;

use App\Presentation\Response\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class RequestParser
{
    public function __construct(private ValidatorInterface $validator) {}

    public function body(Request $request): array
    {
        return json_decode($request->getContent(), true) ?? [];
    }

    /**
     * Valide un DTO et retourne une JsonResponse d'erreur si invalide, null sinon.
     */
    public function validate(object $dto): ?JsonResponse
    {
        $violations = $this->validator->validate($dto);

        if (count($violations) === 0) {
            return null;
        }

        $errors = [];
        foreach ($violations as $violation) {
            $errors[$violation->getPropertyPath()][] = $violation->getMessage();
        }

        return ApiResponse::error('Données invalides.', 422, $errors);
    }
}
