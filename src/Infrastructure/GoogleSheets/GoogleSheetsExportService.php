<?php

namespace App\Infrastructure\GoogleSheets;

use App\Entity\Payment;
use App\Infrastructure\GoogleSheets\Dto\PaymentExportPayload;

/**
 * PostgreSQL → Google Sheets export.
 * Данные формируются из сущностей, а не из Forminator.
 */
class GoogleSheetsExportService
{
    public function __construct(
        private readonly GoogleSheetsClient $client,
    ) {
    }

    public function exportSuccessfulPayment(Payment $payment): void
    {
        $application = $payment->getApplication();
        $user = $application?->getUser();

        if (!$application || !$user) {
            return;
        }

        $totalAmount = $application->getTotalAmount();
        $paidTotal = $application->getPaidAmount();
        $remaining = max(0, $totalAmount - $paidTotal);

        $this->client->exportPayment(new PaymentExportPayload(
            email: $user->getEmail(),
            phone: $user->getPhone() ?? '',
            amount: number_format($payment->getAmount(), 2, '.', ''),
            currency: 'RUB',
            paymentId: $payment->getProviderPaymentId() ?? '',
            paidAt: ($payment->getPaidAt() ?? new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            applicationUuid: (string) $application->getUuid(),
            totalAmount: number_format($totalAmount, 2, '.', ''),
            paidTotal: number_format($paidTotal, 2, '.', ''),
            remaining: number_format($remaining, 2, '.', ''),
        ));
    }
}
