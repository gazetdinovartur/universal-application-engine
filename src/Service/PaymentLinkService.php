<?php

namespace App\Service;

use App\Entity\Application;
use App\Entity\PaymentLink;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PaymentLinkService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function createForApplication(Application $application, ?\DateTimeImmutable $expiresAt = null): PaymentLink
    {
        $paymentLink = new PaymentLink();
        $paymentLink->setApplication($application);

        if ($expiresAt) {
            $paymentLink->setExpiresAt($expiresAt);
        }

        $this->entityManager->persist($paymentLink);
        $this->entityManager->flush();

        return $paymentLink;
    }

    public function getValidLink(string $token): PaymentLink
    {
        $paymentLink = $this->entityManager->getRepository(PaymentLink::class)->findOneBy(['token' => $token]);

        if (!$paymentLink || $paymentLink->isExpired()) {
            throw new NotFoundHttpException('Payment link not found or expired.');
        }

        return $paymentLink;
    }
}
