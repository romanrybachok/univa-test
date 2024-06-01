<?php
namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AccessDeniedExceptionListener
{
    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();
        if ($exception instanceof AccessDeniedHttpException) {
            $response = new JsonResponse([
                'error' => 'Access Denied',
                'message' => 'You do not have permission to access this resource.'
            ], 403);

            $event->setResponse($response);
        }
    }
}

