<?php

namespace App\Repository;

use App\Entity\Application;
use App\Entity\Product;
use App\Enum\ApplicationStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Application>
 */
class ApplicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Application::class);
    }

    public function findOneByUuid(Uuid|string $uuid): ?Application
    {
        return $this->findOneBy(['uuid' => $uuid]);
    }

    public function findActiveDuplicateByEmail(string $email, Product $product): ?Application
    {
        return $this->createQueryBuilder('a')
            ->innerJoin('a.user', 'u')
            ->andWhere('u.email = :email')
            ->andWhere('a.product = :product')
            ->andWhere('a.status != :cancelled')
            ->setParameter('email', $email)
            ->setParameter('product', $product)
            ->setParameter('cancelled', ApplicationStatus::Cancelled)
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
