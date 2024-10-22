<?php

namespace App\Controller\Admin;

use App\Entity\DistributionPoint;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

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
            TextField::new('address', 'Adresse')->hideOnIndex(), // Champ pour l'adresse
            TextEditorField::new('description', 'Description'), // Champ pour la description, masqué dans l'index
            ChoiceField::new('type', 'Type')
            ->setChoices([
                'Marché' => 'Marché',
                'Maraîcher' => 'Maraîcher',
                'AMAP' => 'AMAP',
                'Magasin' => 'Magasin',
                'Epicerie' => 'Epicerie',
                'Restaurant' => 'Restaurant',
            ])
            ->setRequired(false),
            TextField::new('site', 'Site internet')->hideOnIndex(),
        ];
    }
}