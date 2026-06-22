<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

#[AsEventListener(event: 'kernel.request', priority: 9999)]
#[AsEventListener(event: 'kernel.response', priority: 0)]
class CorsListener
{
    /** @var list<string> */
    private array $allowedOrigins;

    public function __construct(
        string $allowOrigin,
    ) {
        $this->allowedOrigins = array_values(array_filter(array_map('trim', explode(',', $allowOrigin))));
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        if ($request->getMethod() !== 'OPTIONS') {
            return;
        }

        $response = new Response('', Response::HTTP_NO_CONTENT);
        $this->addCorsHeaders($response, $request);
        $event->setResponse($response);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $this->addCorsHeaders($event->getResponse(), $request);
    }

    private function addCorsHeaders(Response $response, Request $request): void
    {
        $origin = $request->headers->get('Origin');
        $allowed = $this->resolveAllowedOrigin($origin);

        if ($allowed !== null) {
            $response->headers->set('Access-Control-Allow-Origin', $allowed);
            $response->headers->set('Vary', 'Origin');
        }

        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');
        $response->headers->set('Access-Control-Max-Age', '3600');
    }

    private function resolveAllowedOrigin(?string $origin): ?string
    {
        if ($this->allowedOrigins === []) {
            return null;
        }

        if ($origin !== null && in_array($origin, $this->allowedOrigins, true)) {
            return $origin;
        }

        if (count($this->allowedOrigins) === 1) {
            return $this->allowedOrigins[0];
        }

        return null;
    }
}
