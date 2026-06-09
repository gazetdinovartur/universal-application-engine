<?php

namespace App\Service;

use App\DTO\CalculatePriceRequest;
use App\DTO\PricingResult;
use App\Entity\ParticipationOption;
use App\Entity\ParticipationPrice;
use App\Entity\PricingPeriod;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FestivalPricingCalculator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function calculate(CalculatePriceRequest $request): PricingResult
    {
        $product = $this->entityManager->getRepository(Product::class)->findOneBy([
            'slug' => $request->productSlug,
            'isActive' => true,
        ]);

        if (!$product) {
            throw new NotFoundHttpException(sprintf('Product "%s" not found.', $request->productSlug));
        }

        $participationOption = $this->entityManager->getRepository(ParticipationOption::class)->findOneBy([
            'product' => $product,
            'code' => $request->participationOptionCode,
        ]);

        if (!$participationOption) {
            throw new NotFoundHttpException(sprintf('Participation option "%s" not found.', $request->participationOptionCode));
        }

        $registrationDate = $request->registrationDate ?? new \DateTimeImmutable();
        $pricingPeriod = $this->resolvePricingPeriod($product, $registrationDate);
        $participationPrice = $this->resolveParticipationPrice($pricingPeriod, $participationOption);

        $totalAmount = $participationPrice->getPrice();
        $discountAmount = 0;
        $payNowPercent = $request->payNowPercent ?? 100;
        $payNowAmount = (int) round($totalAmount * $payNowPercent / 100);

        return new PricingResult(
            totalAmount: $totalAmount,
            discountAmount: $discountAmount,
            payNowAmount: $payNowAmount,
            pricingPeriodName: $pricingPeriod->getName(),
            participationOptionName: $participationOption->getName(),
        );
    }

    private function resolvePricingPeriod(Product $product, \DateTimeImmutable $date): PricingPeriod
    {
        $periods = $this->entityManager->getRepository(PricingPeriod::class)->findBy(
            ['product' => $product, 'isActive' => true],
            ['startAt' => 'ASC'],
        );

        foreach ($periods as $period) {
            if ($date >= $period->getStartAt() && $date <= $period->getEndAt()) {
                return $period;
            }
        }

        throw new NotFoundHttpException('No active pricing period found for the given date.');
    }

    private function resolveParticipationPrice(PricingPeriod $pricingPeriod, ParticipationOption $participationOption): ParticipationPrice
    {
        $price = $this->entityManager->getRepository(ParticipationPrice::class)->findOneBy([
            'pricingPeriod' => $pricingPeriod,
            'participationOption' => $participationOption,
        ]);

        if (!$price) {
            throw new NotFoundHttpException('Price not configured for the selected period and participation option.');
        }

        return $price;
    }
}
