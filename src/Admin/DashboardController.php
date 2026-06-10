<?php

namespace App\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Universal Application Engine');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Главная', 'fa fa-home');

        yield MenuItem::section('Основное');
        yield MenuItem::linkTo(ApplicationCrudController::class, 'Заявки', 'fa fa-file-alt');
        yield MenuItem::linkTo(PaymentCrudController::class, 'Платежи', 'fa fa-credit-card');
        yield MenuItem::linkTo(UserCrudController::class, 'Пользователи', 'fa fa-users');

        yield MenuItem::section('Проекты и цены');
        yield MenuItem::linkTo(ProductCrudController::class, 'Проекты', 'fa fa-box');
        yield MenuItem::linkTo(PricingPeriodCrudController::class, 'Периоды стоимости', 'fa fa-calendar');
        yield MenuItem::linkTo(ParticipationOptionCrudController::class, 'Варианты участия', 'fa fa-list');
        yield MenuItem::linkTo(ParticipationPriceCrudController::class, 'Цены участия', 'fa fa-ruble-sign');
    }
}
