<?php

namespace App\Admin;

use App\Entity\ParticipationPrice;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;

class ParticipationPriceCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ParticipationPrice::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Participation Price')
            ->setEntityLabelInPlural('Participation Prices');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('pricingPeriod');
        yield AssociationField::new('participationOption');
        yield IntegerField::new('price')->setLabel('Price (₽)');
    }
}
