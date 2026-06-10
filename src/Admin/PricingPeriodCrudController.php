<?php

namespace App\Admin;

use App\Entity\PricingPeriod;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class PricingPeriodCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return PricingPeriod::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Период стоимости')
            ->setEntityLabelInPlural('Периоды стоимости')
            ->setDefaultSort(['startAt' => 'ASC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $editPrices = Action::new('editPrices', 'Цены')
            ->setIcon('fa fa-table')
            ->linkToRoute('admin_pricing_period_prices', static function (PricingPeriod $period): array {
                return ['id' => $period->getId()];
            });

        return $actions
            ->add(Crud::PAGE_INDEX, $editPrices)
            ->add(Crud::PAGE_DETAIL, $editPrices);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('product')->setLabel('Проект');
        yield TextField::new('name')->setLabel('Название периода');
        yield DateTimeField::new('startAt')->setLabel('Начало');
        yield DateTimeField::new('endAt')->setLabel('Окончание');
        yield BooleanField::new('isActive')->setLabel('Активен');
        yield ArrayField::new('participationPrices', 'Цены по вариантам участия')
            ->formatValue(static function ($value, PricingPeriod $period): array {
                $result = [];
                foreach ($period->getParticipationPrices() as $price) {
                    $optionName = $price->getParticipationOption()?->getName() ?? 'Вариант';
                    $result[] = sprintf('%s — %d ₽', $optionName, $price->getPrice());
                }

                return $result;
            })
            ->onlyOnDetail();
    }

    public function detail(AdminContext $context): KeyValueStore|\Symfony\Component\HttpFoundation\RedirectResponse
    {
        $periodId = (int) $context->getEntity()->getPrimaryKeyValue();

        return $this->redirectToRoute('admin_pricing_period_prices', ['id' => $periodId]);
    }
}
