<?php

namespace App\Controller\Api;

use App\DTO\CreatePaymentRequest;
use App\Service\PaymentLinkService;
use App\Service\PaymentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class PaymentController extends AbstractController
{
    public function __construct(
        private readonly PaymentLinkService $paymentLinkService,
        private readonly PaymentService $paymentService,
    ) {
    }

    /**
     * Аналог WP REST: POST /wp-json/yk/v1/create
     */
    #[Route('/payments', name: 'api_payments_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = $request->toArray();

        $createRequest = new CreatePaymentRequest(
            email: $data['email'] ?? '',
            phone: $data['phone'] ?? '',
            amount: (int) ($data['amount'] ?? 0),
            manualAmount: isset($data['manual']) ? (int) $data['manual'] : null,
            applicationUuid: $data['applicationUuid'] ?? null,
        );

        $result = $this->paymentService->createYookassaPayment($createRequest);

        return $this->json($result);
    }

    /**
     * Аналог WP REST: GET /wp-json/yk/v1/check/{id}
     */
    #[Route('/payments/{id}/status', name: 'api_payments_status', methods: ['GET'])]
    public function status(string $id): JsonResponse
    {
        return $this->json($this->paymentService->getPaymentStatus($id));
    }

    #[Route('/payment-links/{token}', name: 'api_payment_links_show', methods: ['GET'])]
    public function showPaymentLink(string $token): JsonResponse
    {
        $paymentLink = $this->paymentLinkService->getValidLink($token);
        $application = $paymentLink->getApplication();

        return $this->json([
            'token' => $paymentLink->getToken(),
            'expiresAt' => $paymentLink->getExpiresAt()->format(\DateTimeInterface::ATOM),
            'application' => [
                'uuid' => (string) $application?->getUuid(),
                'totalAmount' => $application?->getTotalAmount(),
                'paidAmount' => $application?->getPaidAmount(),
                'remainingAmount' => ($application?->getTotalAmount() ?? 0) - ($application?->getPaidAmount() ?? 0),
            ],
        ]);
    }
}
