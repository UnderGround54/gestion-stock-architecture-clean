<?php

namespace App\Presentation\Response;


use Symfony\Component\HttpFoundation\JsonResponse;

final class ApiResponse
{
    public static function success(
        mixed $data = null,
        string $message = 'Opération réussie',
        int $statusCode = 200,
        array $meta = []
    ): JsonResponse {
        $body = [
            'success' => true,
            'code'    => $statusCode,
            'message' => $message,
            'data'    => $data,
        ];

        if (!empty($meta)) {
            $body['meta'] = $meta;
        }

        return new JsonResponse($body, $statusCode);
    }

    public static function created(
        mixed $data = null,
        string $message = 'Ressource créée avec succès'
    ): JsonResponse {
        return self::success($data, $message, 201);
    }

    public static function error(
        string $message,
        int $statusCode = 400,
        array $errors = []
    ): JsonResponse {
        $body = [
            'success' => false,
            'code'    => $statusCode,
            'message' => $message,
            'data'    => null,
        ];

        if (!empty($errors)) {
            $body['errors'] = $errors;
        }

        return new JsonResponse($body, $statusCode);
    }

    public static function paginated(
        array $items,
        int $total,
        int $page,
        int $limit,
        string $message = 'Liste récupérée avec succès'
    ): JsonResponse {
        return self::success(
            data: $items,
            message: $message,
            meta: [
                'pagination' => [
                    'total'       => $total,
                    'page'        => $page,
                    'limit'       => $limit,
                    'total_pages' => (int) ceil($total / $limit),
                    'has_next'    => $page < ceil($total / $limit),
                    'has_prev'    => $page > 1,
                ],
            ]
        );
    }
}

