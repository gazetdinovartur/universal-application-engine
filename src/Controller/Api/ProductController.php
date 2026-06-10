<?php

namespace App\Controller\Api;

use App\Entity\ParticipationOption;
use App\Entity\ParticipationPrice;
use App\Entity\PricingPeriod;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/products')]
class ProductController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/{slug}', name: 'api_products_show', methods: ['GET'])]
    public function show(string $slug): JsonResponse
    {
        $product = $this->entityManager->getRepository(Product::class)->findOneBy([
            'slug' => $slug,
            'isActive' => true,
        ]);

        if (!$product) {
            throw new NotFoundHttpException('Product not found');
        }

        $options = $this->entityManager->getRepository(ParticipationOption::class)->findBy(
            ['product' => $product],
            ['name' => 'ASC'],
        );

        $periods = $this->entityManager->getRepository(PricingPeriod::class)->findBy(
            ['product' => $product, 'isActive' => true],
            ['startAt' => 'ASC'],
        );

        $now = new \DateTimeImmutable();
        $activePeriod = null;
        foreach ($periods as $period) {
            if ($now >= $period->getStartAt() && $now <= $period->getEndAt()) {
                $activePeriod = $period;
                break;
            }
        }

        $prices = [];
        if ($activePeriod) {
            $priceRows = $this->entityManager->getRepository(ParticipationPrice::class)->findBy([
                'pricingPeriod' => $activePeriod,
            ]);
            foreach ($priceRows as $row) {
                $prices[$row->getParticipationOption()?->getCode() ?? ''] = $row->getPrice();
            }
        }

        return $this->json([
            'slug' => $product->getSlug(),
            'name' => $product->getName(),
            'participationOptions' => array_map(static fn (ParticipationOption $o) => [
                'code' => $o->getCode(),
                'name' => $o->getName(),
                'price' => $prices[$o->getCode()] ?? null,
            ], $options),
            'activePricingPeriod' => $activePeriod ? [
                'name' => $activePeriod->getName(),
                'startAt' => $activePeriod->getStartAt()->format(\DateTimeInterface::ATOM),
                'endAt' => $activePeriod->getEndAt()->format(\DateTimeInterface::ATOM),
            ] : null,
        ]);
    }
}
