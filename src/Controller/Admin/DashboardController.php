<?php
namespace App\Controller\Admin;

use App\Entity\Product;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard; // AJOUTEZ CETTE LIGNE
use Symfony\Component\HttpFoundation\Response;
// SUPPRIMEZ CETTE LIGNE : use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractDashboardController
{
    // REMPLACEZ #[Route('/admin', name: 'admin')] PAR :
    #[AdminDashboard(routePath: '/admin', routeName: 'admin')]
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('💀 La Petite Maison de l\'Épouvante')
            ->setFaviconPath('favicon.ico');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Produits', 'fa fa-box', Product::class);
        yield MenuItem::linkToCrud('Utilisateurs', 'fa fa-users', User::class);
        yield MenuItem::linkToRoute('Retour au site', 'fa fa-arrow-left', 'app_home');
    }
}