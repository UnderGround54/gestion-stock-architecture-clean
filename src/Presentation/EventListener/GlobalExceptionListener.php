<?php

namespace App\Presentation\EventListener;

use App\Domain\Exception\ClientNotFoundException;
use App\Domain\Exception\DomainException;
use App\Domain\Exception\InvoiceNotFoundException;
use App\Domain\Exception\OrderNotFoundException;
use App\Domain\Exception\ProductNotFoundException;
use App\Presentation\Response\ApiResponse;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\Exception\ValidationFailedException;

#[AsEventListener(event: KernelEvents::EXCEPTION)]
final class GlobalExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $response = match (true) {
            // Exceptions 404 Domain
            $exception instanceof ClientNotFoundException,
                $exception instanceof ProductNotFoundException,
                $exception instanceof OrderNotFoundException,
                $exception instanceof InvoiceNotFoundException
            => ApiResponse::error($exception->getMessage(), 404),

            // Exceptions métier
            $exception instanceof DomainException
            => ApiResponse::error($exception->getMessage(), 422),

            // Validation Symfony
            $exception instanceof ValidationFailedException
            => $this->handleValidationException($exception),

            // HTTP 404
            $exception instanceof NotFoundHttpException
            => ApiResponse::error('Route introuvable : ' . $exception->getMessage(), 404),

            // HTTP 405
            $exception instanceof MethodNotAllowedHttpException
            => ApiResponse::error('Méthode HTTP non autorisée : ' . $exception->getMessage(), 405),

            // Erreur serveur générique
            default => ApiResponse::error(
                'Une erreur interne est survenue : ' . $exception->getMessage(),
                500
            ),
        };

        $event->setResponse($response);
    }

    private function handleValidationException(ValidationFailedException $e): JsonResponse
    {
        $errors = [];
        foreach ($e->getViolations() as $violation) {
            $errors[$violation->getPropertyPath()][] = $violation->getMessage();
        }

        return ApiResponse::error('Données invalides.', 422, $errors);
    }
}
