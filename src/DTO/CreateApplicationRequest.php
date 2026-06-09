<?php

namespace App\DTO;

class CreateApplicationRequest
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $phone,
        public readonly string $productSlug,
        public readonly string $participationOptionCode,
        /** @var array<string, mixed> */
        public readonly array $payload = [],
        public readonly ?int $payNowAmount = null,
    ) {
    }
}
