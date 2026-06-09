<?php

namespace App\Admin;

use App\Entity\Application;
use App\Enum\ApplicationStatus;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ApplicationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Application::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Application')
            ->setEntityLabelInPlural('Applications')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('uuid')->hideOnForm();
        yield AssociationField::new('user');
        yield AssociationField::new('product');
        yield AssociationField::new('pricingPeriod');
        yield ChoiceField::new('status')
            ->setChoices([
                'New' => ApplicationStatus::New,
                'Partially Paid' => ApplicationStatus::PartiallyPaid,
                'Paid' => ApplicationStatus::Paid,
                'Cancelled' => ApplicationStatus::Cancelled,
            ]);
        yield IntegerField::new('totalAmount')->setLabel('Total (₽)');
        yield IntegerField::new('paidAmount')->setLabel('Paid (₽)');
        yield CodeEditorField::new('payload')->onlyOnDetail();
        yield DateTimeField::new('createdAt')->hideOnForm();
        yield DateTimeField::new('updatedAt')->hideOnForm();
    }
}
