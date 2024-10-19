<?php

namespace App\Controller\Admin;

use App\Entity\DistributionPoint;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class DistributionPointCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return DistributionPoint::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(), // Masquer l'ID dans le formulaire
            TextField::new('name', 'Nom du point'), // Champ pour le nom
            TextField::new('address', 'Adresse'), // Champ pour l'adresse
            TextField::new('description', 'Description')->hideOnIndex(), // Champ pour la description, masqué dans l'index
        ];
    }
}