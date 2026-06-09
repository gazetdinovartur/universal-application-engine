<?php

namespace App\DTO;

readonly class CreatePaymentRequest
{
    public function __construct(
        public string $email,
        public string $phone,
        public int $amount,
        public ?int $manualAmount = null,
        public ?string $applicationUuid = null,
    ) {
    }

    public function resolveAmount(): int
    {
        if ($this->manualAmount !== null && $this->manualAmount > 0) {
            return $this->manualAmount;
        }

        return max(0, $this->amount);
    }
}
