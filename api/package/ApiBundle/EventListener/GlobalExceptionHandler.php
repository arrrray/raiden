<?php

namespace Package\ApiBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Global Exception Handler.
 */
class GlobalExceptionHandler implements EventSubscriberInterface
{
    public function onKernelException(ExceptionEvent $event): void
    {
        if ('dev' === $_ENV['APP_ENV']) {
            return;
        }

        // Create Exception Message
        $exception = $event->getThrowable();
        $code = method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : $exception->getCode();
        $message = [
            'type' => (new \ReflectionClass($exception))->getShortName(),
            'code' => isset(Response::$statusTexts[$code]) ? $code : 500,
            'message' => $exception->getMessage(),
        ];

        // Append Fields
        if (method_exists($exception, 'getErrors')) {
            $message['errors'] = $exception->getErrors();
        }

        // Json Response
        $event->setResponse(new JsonResponse($message, $message['code']));
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => [['onKernelException', 50]]];
    }
}
