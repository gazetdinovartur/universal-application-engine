<?php

namespace App\Admin;

use App\Entity\Payment;
use App\Enum\PaymentProvider;
use App\Enum\PaymentStatus;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class PaymentCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Payment::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Payment')
            ->setEntityLabelInPlural('Payments')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('application');
        yield ChoiceField::new('provider')
            ->setChoices([
                'YooKassa' => PaymentProvider::Yookassa,
            ]);
        yield TextField::new('providerPaymentId');
        yield IntegerField::new('amount')->setLabel('Amount (₽)');
        yield ChoiceField::new('status')
            ->setChoices([
                'Pending' => PaymentStatus::Pending,
                'Succeeded' => PaymentStatus::Succeeded,
                'Failed' => PaymentStatus::Failed,
                'Cancelled' => PaymentStatus::Cancelled,
            ]);
        yield DateTimeField::new('paidAt');
        yield DateTimeField::new('createdAt')->hideOnForm();
        yield DateTimeField::new('updatedAt')->hideOnForm();
    }
}
