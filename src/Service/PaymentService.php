<?php

namespace App\Service;

use App\DTO\CreatePaymentRequest;
use App\Entity\Application;
use App\Entity\Payment;
use App\Enum\ApplicationStatus;
use App\Enum\PaymentProvider;
use App\Enum\PaymentStatus;
use App\Infrastructure\GoogleSheets\GoogleSheetsExportService;
use App\Infrastructure\Yookassa\YookassaClient;
use App\Repository\ApplicationRepository;
use App\Repository\PaymentRepository;
use App\Util\PhoneNormalizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Портировано из legacy/wordpress/yookassa-plugin.php.
 */
class PaymentService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PaymentRepository $paymentRepository,
        private readonly ApplicationRepository $applicationRepository,
        private readonly YookassaClient $yookassaClient,
        private readonly GoogleSheetsExportService $googleSheetsExportService,
        private readonly PaymentLinkService $paymentLinkService,
    ) {
    }

    /**
     * @return array{payment_id: string, gateway_url: string}
     */
    public function createYookassaPayment(CreatePaymentRequest $request): array
    {
        $email = filter_var($request->email, FILTER_VALIDATE_EMAIL);
        $phone = PhoneNormalizer::toE164($request->phone);
        $amount = $request->resolveAmount();

        if (!$email || !$phone || $amount <= 0) {
            throw new BadRequestHttpException('Invalid input');
        }

        $application = null;
        if ($request->applicationUuid) {
            $application = $this->applicationRepository->findOneByUuid($request->applicationUuid);
            if (!$application) {
                throw new NotFoundHttpException('Application not found');
            }
        }

        $yookassaResult = $this->yookassaClient->createPayment($email, $phone, $amount);

        $payment = new Payment();
        $payment->setProvider(PaymentProvider::Yookassa);
        $payment->setProviderPaymentId($yookassaResult->paymentId);
        $payment->setAmount($amount);
        $payment->setStatus(PaymentStatus::Pending);

        if ($application) {
            $payment->setApplication($application);
        }

        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        return [
            'payment_id' => $yookassaResult->paymentId,
            'gateway_url' => $yookassaResult->gatewayUrl,
        ];
    }

    /**
     * @param array<string, mixed> $webhookPayload
     */
    public function handleYookassaWebhook(array $webhookPayload): void
    {
        if (!isset($webhookPayload['object']['id'])) {
            throw new BadRequestHttpException('Bad request');
        }

        $paymentId = (string) $webhookPayload['object']['id'];
        $verified = $this->yookassaClient->verifyPayment($paymentId);
        $status = (string) $verified['status'];

        $payment = $this->paymentRepository->findOneByProviderPaymentId(
            PaymentProvider::Yookassa,
            $paymentId,
        );

        if (!$payment) {
            throw new NotFoundHttpException('Payment not found');
        }

        if ($status === 'succeeded') {
            $this->markPaymentSucceeded($payment);
        }

        if ($status === 'canceled') {
            $payment->setStatus(PaymentStatus::Cancelled);
            $this->entityManager->flush();
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getPaymentStatus(string $providerPaymentId): array
    {
        $payment = $this->paymentRepository->findOneByProviderPaymentId(
            PaymentProvider::Yookassa,
            $providerPaymentId,
        );

        if (!$payment) {
            return ['paid' => false];
        }

        $application = $payment->getApplication();
        $user = $application?->getUser();

        return [
            'paid' => $payment->getStatus() === PaymentStatus::Succeeded,
            'status' => strtolower($payment->getStatus()->value),
            'amount' => number_format($payment->getAmount(), 2, '.', ''),
            'email' => $user?->getEmail(),
            'phone' => $user?->getPhone(),
            'payment_id' => $payment->getProviderPaymentId(),
            'updated_at' => $payment->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
            'application_uuid' => $application ? (string) $application->getUuid() : null,
        ];
    }

    public function syncSucceededPaymentsToGoogleSheets(): int
    {
        $payments = $this->paymentRepository->findBy(['status' => PaymentStatus::Succeeded]);
        $count = 0;

        foreach ($payments as $payment) {
            $this->googleSheetsExportService->exportSuccessfulPayment($payment);
            ++$count;
        }

        return $count;
    }

    private function markPaymentSucceeded(Payment $payment): void
    {
        if ($payment->getStatus() === PaymentStatus::Succeeded) {
            return;
        }

        $payment->setStatus(PaymentStatus::Succeeded);
        $payment->setPaidAt(new \DateTimeImmutable());

        $application = $payment->getApplication();
        if ($application) {
            $application->setPaidAmount($application->getPaidAmount() + $payment->getAmount());
            $this->updateApplicationStatus($application);

            if (
                $application->getStatus() === ApplicationStatus::PartiallyPaid
                && $application->getPaymentLinks()->isEmpty()
            ) {
                $this->paymentLinkService->createForApplication($application);
            }
        }

        $this->entityManager->flush();

        if ($application) {
            $this->googleSheetsExportService->exportSuccessfulPayment($payment);
        }
    }

    private function updateApplicationStatus(Application $application): void
    {
        if ($application->getPaidAmount() >= $application->getTotalAmount()) {
            $application->setStatus(ApplicationStatus::Paid);
        } elseif ($application->getPaidAmount() > 0) {
            $application->setStatus(ApplicationStatus::PartiallyPaid);
        }
    }
}
