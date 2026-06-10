<?php

namespace App\Infrastructure\GoogleSheets\Dto;

/**
 * Payload для экспорта в Google Sheets.
 * Поля по именам — GAS v2 ищет колонки по заголовкам, а не по индексам.
 */
readonly class PaymentExportPayload
{
    public function __construct(
        public string $email,
        public string $phone,
        public string $amount,
        public string $currency,
        public string $paymentId,
        public string $paidAt,
        public ?string $applicationUuid = null,
        public ?string $totalAmount = null,
        public ?string $paidTotal = null,
        public ?string $remaining = null,
        public string $action = 'payment',
    ) {
    }

    /** @return array<string, string|null> */
    public function toArray(): array
    {
        return [
            'action' => $this->action,
            'email' => $this->email,
            'phone' => $this->phone,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'paymentId' => $this->paymentId,
            'paidAt' => $this->paidAt,
            'applicationUuid' => $this->applicationUuid,
            'totalAmount' => $this->totalAmount,
            'paidTotal' => $this->paidTotal,
            'remaining' => $this->remaining,
        ];
    }
}
