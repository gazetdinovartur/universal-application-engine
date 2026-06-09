<?php

namespace App\Controller\Api;

use App\Service\PaymentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Аналог WP REST: POST /wp-json/yk/v1/webhook
 */
#[Route('/api/webhooks')]
class WebhookController extends AbstractController
{
    public function __construct(
        private readonly PaymentService $paymentService,
    ) {
    }

    #[Route('/yookassa', name: 'api_webhooks_yookassa', methods: ['POST'])]
    public function yookassa(Request $request): JsonResponse
    {
        try {
            $this->paymentService->handleYookassaWebhook($request->toArray());

            return $this->json(['ok' => true]);
        } catch (HttpExceptionInterface $e) {
            return $this->json(['error' => $e->getMessage()], $e->getStatusCode());
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Internal error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
