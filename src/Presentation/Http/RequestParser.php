<?php

namespace App\Presentation\Http;

use App\Presentation\Response\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class RequestParser
{
    public function __construct(
        private ValidatorInterface $validator,
        private SerializerInterface $serializer
    ) {}

    public function body(Request $request): array
    {
        $content = $request->getContent();

        if (empty($content)) {
            return [];
        }

        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException(
                'Corps de la requête JSON invalide : ' . json_last_error_msg()
            );
        }

        return $data;
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
