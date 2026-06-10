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
            ->setEntityLabelInSingular('Платеж')
            ->setEntityLabelInPlural('Платежи')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('application')->setLabel('Заявка');
        yield ChoiceField::new('provider')
            ->setChoices([
                'YooKassa' => PaymentProvider::Yookassa,
            ])->setLabel('Провайдер');
        yield TextField::new('providerPaymentId')->setLabel('ID платежа у провайдера');
        yield IntegerField::new('amount')->setLabel('Сумма (₽)');
        yield ChoiceField::new('status')
            ->setChoices([
                'Ожидает' => PaymentStatus::Pending,
                'Успешен' => PaymentStatus::Succeeded,
                'Ошибка' => PaymentStatus::Failed,
                'Отменен' => PaymentStatus::Cancelled,
            ])->setLabel('Статус');
        yield DateTimeField::new('paidAt')->setLabel('Оплачен');
        yield DateTimeField::new('createdAt')->setLabel('Создан')->hideOnForm();
        yield DateTimeField::new('updatedAt')->setLabel('Обновлен')->hideOnForm();
    }
}
