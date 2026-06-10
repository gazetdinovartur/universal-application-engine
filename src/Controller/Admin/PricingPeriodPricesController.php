<?php

namespace App\Controller\Admin;

use App\Entity\ParticipationOption;
use App\Entity\ParticipationPrice;
use App\Entity\PricingPeriod;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PricingPeriodPricesController extends AbstractController
{
    #[Route('/admin/pricing-period/{id}/prices', name: 'admin_pricing_period_prices', methods: ['GET', 'POST'])]
    public function edit(
        PricingPeriod $pricingPeriod,
        Request $request,
        EntityManagerInterface $entityManager,
        AdminUrlGenerator $adminUrlGenerator,
    ): Response {
        $options = $entityManager->getRepository(ParticipationOption::class)->findBy([
            'product' => $pricingPeriod->getProduct(),
        ]);
        $options = $this->sortOptionsByFestivalOrder($options);

        $priceRows = $entityManager->getRepository(ParticipationPrice::class)->findBy([
            'pricingPeriod' => $pricingPeriod,
        ]);

        $priceByOptionId = [];
        foreach ($priceRows as $priceRow) {
            $optionId = $priceRow->getParticipationOption()?->getId();
            if (null !== $optionId) {
                $priceByOptionId[$optionId] = $priceRow;
            }
        }

        $formPrices = [];
        foreach ($options as $option) {
            $optionId = $option->getId();
            if (null === $optionId) {
                continue;
            }

            $formPrices[$optionId] = $priceByOptionId[$optionId]?->getPrice() ?? 0;
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid(
                sprintf('pricing_period_prices_%d', $pricingPeriod->getId()),
                (string) $request->request->get('_token')
            )) {
                throw $this->createAccessDeniedException('Invalid CSRF token.');
            }

            $submittedPrices = $request->request->all('prices');
            $hasErrors = false;

            foreach ($options as $option) {
                $optionId = $option->getId();
                if (null === $optionId) {
                    continue;
                }

                $rawValue = $submittedPrices[(string) $optionId] ?? $submittedPrices[$optionId] ?? '';
                $rawValue = trim((string) $rawValue);
                $formPrices[$optionId] = $rawValue;

                if (!preg_match('/^\d+$/', $rawValue)) {
                    $this->addFlash('danger', sprintf('Цена для "%s" должна быть целым числом.', $option->getName()));
                    $hasErrors = true;

                    continue;
                }

                $priceValue = (int) $rawValue;
                $price = $priceByOptionId[$optionId] ?? new ParticipationPrice();
                $price->setPricingPeriod($pricingPeriod);
                $price->setParticipationOption($option);
                $price->setPrice($priceValue);
                $entityManager->persist($price);
                $priceByOptionId[$optionId] = $price;
            }

            if (!$hasErrors) {
                $entityManager->flush();
                $this->addFlash('success', 'Цены периода обновлены.');

                return $this->redirectToRoute('admin_pricing_period_prices', ['id' => $pricingPeriod->getId()]);
            }
        }

        return $this->render('admin/pricing_period_prices.html.twig', [
            'period' => $pricingPeriod,
            'options' => $options,
            'formPrices' => $formPrices,
            'periodsIndexUrl' => $adminUrlGenerator
                ->setController(\App\Admin\PricingPeriodCrudController::class)
                ->setAction(Crud::PAGE_INDEX)
                ->generateUrl(),
        ]);
    }

    /**
     * @param list<ParticipationOption> $options
     *
     * @return list<ParticipationOption>
     */
    private function sortOptionsByFestivalOrder(array $options): array
    {
        $orderMap = [
            'OWN_HOUSE_NO_FOOD' => 10,
            'OWN_HOUSE_FOOD' => 20,
            'OUR_TENT_NO_FOOD' => 30,
            'OUR_TENT_FOOD' => 40,
            'ONE_DAY' => 50,
            'ONE_DAY_FOOD' => 60,
        ];

        usort($options, static function (ParticipationOption $left, ParticipationOption $right) use ($orderMap): int {
            $leftOrder = $orderMap[$left->getCode()] ?? 999;
            $rightOrder = $orderMap[$right->getCode()] ?? 999;

            if ($leftOrder === $rightOrder) {
                return strcmp($left->getName(), $right->getName());
            }

            return $leftOrder <=> $rightOrder;
        });

        return $options;
    }
}
