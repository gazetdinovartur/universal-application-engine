<?php

namespace App\Controller\Api;

use App\DTO\CalculatePriceRequest;
use App\DTO\CreateApplicationRequest;
use App\Service\ApplicationService;
use App\Service\FestivalPricingCalculator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class ApplicationController extends AbstractController
{
    public function __construct(
        private readonly FestivalPricingCalculator $pricingCalculator,
        private readonly ApplicationService $applicationService,
    ) {
    }

    #[Route('/calculate', name: 'api_calculate', methods: ['POST'])]
    public function calculate(Request $request): JsonResponse
    {
        $data = $request->toArray();
        $calculateRequest = new CalculatePriceRequest(
            productSlug: $data['productSlug'] ?? '',
            participationOptionCode: $data['participationOptionCode'] ?? '',
            payNowPercent: isset($data['payNowPercent']) ? (int) $data['payNowPercent'] : null,
        );

        $result = $this->pricingCalculator->calculate($calculateRequest);

        return $this->json([
            'totalAmount' => $result->totalAmount,
            'discountAmount' => $result->discountAmount,
            'payNowAmount' => $result->payNowAmount,
            'pricingPeriodName' => $result->pricingPeriodName,
            'participationOptionName' => $result->participationOptionName,
        ]);
    }

    #[Route('/applications', name: 'api_applications_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = $request->toArray();
        $createRequest = new CreateApplicationRequest(
            name: $data['name'] ?? '',
            email: $data['email'] ?? '',
            phone: $data['phone'] ?? null,
            productSlug: $data['productSlug'] ?? '',
            participationOptionCode: $data['participationOptionCode'] ?? '',
            payload: $data['payload'] ?? [],
            payNowAmount: isset($data['payNowAmount']) ? (int) $data['payNowAmount'] : null,
        );

        $application = $this->applicationService->create($createRequest);

        return $this->json([
            'uuid' => (string) $application->getUuid(),
            'status' => $application->getStatus()->value,
        ], Response::HTTP_CREATED);
    }
}
