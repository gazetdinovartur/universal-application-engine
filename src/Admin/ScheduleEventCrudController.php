<?php

namespace App\Admin;

use App\Entity\ScheduleEvent;
use App\Enum\ScheduleEventType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;

class ScheduleEventCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ScheduleEvent::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Событие расписания')
            ->setEntityLabelInPlural('Расписание')
            ->setDefaultSort(['startsAt' => 'ASC'])
            ->setPaginatorPageSize(50);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('product')->setLabel('Проект'))
            ->add(EntityFilter::new('venue')->setLabel('Площадка'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('product')->setLabel('Проект');
        yield AssociationField::new('venue')->setLabel('Площадка');
        yield DateTimeField::new('startsAt')->setLabel('Начало');
        yield DateTimeField::new('endsAt')->setLabel('Конец');
        yield TextField::new('title')->setLabel('Название');
        yield ChoiceField::new('eventType')
            ->setLabel('Тип')
            ->setChoices([
                'Программа' => ScheduleEventType::Program,
                'Питание' => ScheduleEventType::Meal,
                'Сервис' => ScheduleEventType::Service,
                'Скрыто' => ScheduleEventType::Hidden,
            ]);
        yield BooleanField::new('isPublished')->setLabel('Опубликовано');
        yield TextField::new('externalKey')->setLabel('Ключ импорта')->hideOnIndex();
    }
}
