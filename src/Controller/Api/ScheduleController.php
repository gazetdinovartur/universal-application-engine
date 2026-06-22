<?php

namespace App\Controller\Api;

use App\Entity\Product;
use App\Service\ScheduleQueryService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/products')]
class ScheduleController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ScheduleQueryService $scheduleQueryService,
    ) {
    }

    #[Route('/{slug}/schedule', name: 'api_products_schedule', methods: ['GET'])]
    public function show(string $slug): JsonResponse
    {
        $product = $this->entityManager->getRepository(Product::class)->findOneBy([
            'slug' => $slug,
            'isActive' => true,
        ]);

        if (!$product) {
            throw new NotFoundHttpException('Product not found');
        }

        $payload = $this->scheduleQueryService->getScheduleForProduct($product);

        $response = $this->json($payload);
        $response->setPublic();
        $response->setMaxAge(300);

        return $response;
    }
}
