<?php

namespace App\Controller\Admin;

use App\Entity\Gallery;
use App\Service\GalleryService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use Vich\UploaderBundle\Form\Type\VichImageType;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class GalleryCrudController extends AbstractCrudController
{
    private $galleryService;
    private $entityManager;

    public function __construct(GalleryService $galleryService, EntityManagerInterface $entityManager)
    {
        $this->galleryService = $galleryService;
        $this->entityManager = $entityManager;
    }

    public static function getEntityFqcn(): string
    {
        return Gallery::class;
    }
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_NEW, 'Ajouter une nouvelle Photo')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier la Photo')
            ->setEntityLabelInSingular('Photo')
            ->setEntityLabelInPlural('Photos');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('title', 'Titre'),
            TextareaField::new('description', 'Description')->hideOnIndex(),
            // Champ de catégorie avec des choix prédéfinis
            ChoiceField::new('category', 'Catégorie')
            ->setChoices([
                'Pleurote' => 'Pleurote',
                'Shiitake' => 'Shiitake',
                'Recette' => 'Recette',
                'Mixte' => 'Mixte',
            ])
            ->setRequired(false),  // Permet de laisser le champ vide

            // Gestion des images via VichUploader
            TextField::new('imageGalFile', 'Télécharger une image')
                ->setFormType(VichImageType::class)
                ->onlyOnForms(),

            // Affichage de l'image uploadée
            ImageField::new('imageGalName', 'Image actuelle')
                ->setBasePath('/images/gallery')
                ->onlyOnIndex(),

        ];
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Gallery) return;

        // Handle the image upload
        $this->galleryService->handleImageUpload($entityInstance);

        parent::updateEntity($entityManager, $entityInstance);
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Gallery) return;

        // Handle the image upload
        $this->galleryService->handleImageUpload($entityInstance);

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Gallery) return;

        // Handle the image removal
        $this->galleryService->handleImageRemoval($entityInstance);

        parent::deleteEntity($entityManager, $entityInstance);
    }
}