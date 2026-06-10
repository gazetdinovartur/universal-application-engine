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
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        yield MenuItem::section('Заявки и оплаты');
        yield MenuItem::linkTo(ApplicationCrudController::class, 'Applications', 'fa fa-file-alt');
        yield MenuItem::linkTo(PaymentCrudController::class, 'Payments', 'fa fa-credit-card');
        yield MenuItem::linkTo(UserCrudController::class, 'Users', 'fa fa-users');

        yield MenuItem::section('Продукты и цены');
        yield MenuItem::linkTo(ProductCrudController::class, 'Products', 'fa fa-box');
        yield MenuItem::linkTo(PricingPeriodCrudController::class, 'Pricing Periods', 'fa fa-calendar');
        yield MenuItem::linkTo(ParticipationOptionCrudController::class, 'Participation Options', 'fa fa-list');
        yield MenuItem::linkTo(ParticipationPriceCrudController::class, 'Participation Prices', 'fa fa-ruble-sign');
    }
}
