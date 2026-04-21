<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Exception\InsufficientProductQuantityException;
use App\Exception\OrderNotFoundException;
use App\Exception\ProductNotFoundException;
use App\Exception\ValidationException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

final class JsonExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof ValidationException) {
            $event->setResponse(new JsonResponse([
                'message' => $exception->getMessage(),
                'errors' => $exception->getErrors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY));

            return;
        }

        if ($exception instanceof ProductNotFoundException || $exception instanceof OrderNotFoundException) {
            $event->setResponse(new JsonResponse([
                'message' => $exception->getMessage(),
            ], Response::HTTP_NOT_FOUND));

            return;
        }

        if ($exception instanceof InsufficientProductQuantityException) {
            $event->setResponse(new JsonResponse([
                'message' => $exception->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY));

            return;
        }

        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();

            $event->setResponse(new JsonResponse([
                'message' => $exception->getMessage() !== ''
                    ? $exception->getMessage()
                    : (Response::$statusTexts[$statusCode] ?? 'HTTP error'),
            ], $statusCode, $exception->getHeaders()));
        }
    }
}
