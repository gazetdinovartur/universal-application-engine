<?php

namespace App\Admin;

use App\Entity\PricingPeriod;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
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
            ->setEntityLabelInSingular('Pricing Period')
            ->setEntityLabelInPlural('Pricing Periods')
            ->setDefaultSort(['startAt' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('product');
        yield TextField::new('name');
        yield DateTimeField::new('startAt');
        yield DateTimeField::new('endAt');
        yield BooleanField::new('isActive');
    }
}
