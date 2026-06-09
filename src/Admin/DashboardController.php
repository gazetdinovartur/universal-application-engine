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
        yield MenuItem::linkToCrud('Applications', 'fa fa-file-alt', \App\Entity\Application::class);
        yield MenuItem::linkToCrud('Payments', 'fa fa-credit-card', \App\Entity\Payment::class);
        yield MenuItem::linkToCrud('Users', 'fa fa-users', \App\Entity\User::class);

        yield MenuItem::section('Продукты и цены');
        yield MenuItem::linkToCrud('Products', 'fa fa-box', \App\Entity\Product::class);
        yield MenuItem::linkToCrud('Pricing Periods', 'fa fa-calendar', \App\Entity\PricingPeriod::class);
        yield MenuItem::linkToCrud('Participation Options', 'fa fa-list', \App\Entity\ParticipationOption::class);
        yield MenuItem::linkToCrud('Participation Prices', 'fa fa-ruble-sign', \App\Entity\ParticipationPrice::class);
    }
}
