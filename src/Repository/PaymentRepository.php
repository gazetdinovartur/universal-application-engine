<?php

namespace App\Repository;

use App\Entity\Payment;
use App\Enum\PaymentProvider;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Payment>
 */
class PaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

    public function findOneByProviderPaymentId(PaymentProvider $provider, string $providerPaymentId): ?Payment
    {
        return $this->findOneBy([
            'provider' => $provider,
            'providerPaymentId' => $providerPaymentId,
        ]);
    }
}
