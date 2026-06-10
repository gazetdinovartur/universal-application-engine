<?php

namespace App\DTO;

use App\Entity\ParticipationOption;
use App\Entity\PricingPeriod;
use App\Entity\Product;

readonly class PricingContext
{
    public function __construct(
        public PricingResult $result,
        public Product $product,
        public PricingPeriod $pricingPeriod,
        public ParticipationOption $participationOption,
    ) {
    }
}
