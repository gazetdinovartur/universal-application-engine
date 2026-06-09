<?php

namespace App\DTO;

class PricingResult
{
    public function __construct(
        public readonly int $totalAmount,
        public readonly int $discountAmount,
        public readonly int $payNowAmount,
        public readonly string $pricingPeriodName,
        public readonly string $participationOptionName,
    ) {
    }
}
