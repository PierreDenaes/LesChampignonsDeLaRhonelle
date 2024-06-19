<?php

namespace App\Controller\Admin;

use App\Entity\Sponsor;
use App\Controller\VichImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class SponsorCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Sponsor::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('nom');
        yield TextField::new('siteWeb', 'Site web');
        yield ImageField::new('image')
            ->setBasePath('/images/sponsors')
            ->setUploadDir('public/images/sponsors')  // définissez le répertoire d'upload ici
            ->onlyOnIndex();
    
        yield DateTimeField::new('updatedAt', 'Modifié le')
            ->setFormTypeOptions(['disabled' => true])  // Disable field to prevent manual edits
            ->onlyOnIndex();

        yield VichImageField::new('imageFile', 'Image File')
            ->setTemplatePath('admin/field/vich_image_widget.html.twig') // chemin vers votre nouveau template personnalisé
            ->hideOnIndex();
    }
}