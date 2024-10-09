<?php

namespace App\Controller\Admin;

use App\Entity\Unit;
use App\Entity\User;
use App\Entity\Recipe;
use App\Entity\Sponsor;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;

class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        

        // Option 1. You can make your dashboard redirect to some common page of your backend
        //
         $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
         return $this->redirect($adminUrlGenerator->setController(SponsorCrudController::class)->generateUrl());

        // Option 2. You can make your dashboard redirect to different pages depending on the user
        //
        // if ('jane' === $this->getUser()->getUsername()) {
        //     return $this->redirect('...');
        // }

        // Option 3. You can render some custom template to display a proper dashboard with widgets, etc.
        // (tip: it's easier if your template extends from @EasyAdmin/page/content.html.twig)
        //
        // return $this->render('some/path/my-dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
          
            ->setTitle('<div class="text-center"> <img src="images/elements/logo-100.webp"><br> Les Champignons De La Rhonelle </div>')
            ->setFaviconPath('favicon/favicon-48x48.png')
            ->setLocales(['fr', 'en'])
            ;
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::section('Administration');
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Utillisateurs', 'fa-solid fa-user', User::class);
        yield MenuItem::linkToCrud('Nos sponsors', 'fa-solid fa-handshake', Sponsor::class);
        yield MenuItem::linkToCrud('Les recettes', 'fa-solid fa-blender', Recipe::class);
        yield MenuItem::linkToCrud('Les unités de mesure', 'fa-solid fa-ruler', Unit::class);

        yield MenuItem::section('Vue d\'ensemble');
        yield MenuItem::linkToRoute('Retour au site', 'fa-solid fa-home', 'app_home');
        yield MenuItem::linkToRoute('Vue Profil', 'fa-solid fa-user', 'app_profile');
        
    }
}
