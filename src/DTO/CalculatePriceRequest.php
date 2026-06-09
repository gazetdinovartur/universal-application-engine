<?php

namespace App\DTO;

class CalculatePriceRequest
{
    public function __construct(
        public readonly string $productSlug,
        public readonly string $participationOptionCode,
        public readonly ?\DateTimeImmutable $registrationDate = null,
        public readonly ?int $payNowPercent = null,
    ) {
    }
}
