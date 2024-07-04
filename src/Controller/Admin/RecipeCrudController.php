<?php
namespace App\Controller\Admin;

use App\Entity\Recipe;
use App\Entity\Ingredient;
use App\Entity\RecipeStep;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use Vich\UploaderBundle\Form\Type\VichImageType;
use App\Form\IngredientType;
use App\Form\RecipeStepType;
use App\Service\RecipeService;
use Doctrine\ORM\EntityManagerInterface;

class RecipeCrudController extends AbstractCrudController
{
    private $recipeService;
    private $entityManager;

    public function __construct(RecipeService $recipeService, EntityManagerInterface $entityManager)
    {
        $this->recipeService = $recipeService;
        $this->entityManager = $entityManager;
    }

    public static function getEntityFqcn(): string
    {
        return Recipe::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('title', 'Titre'),
            TextEditorField::new('description', 'Description'),
            ImageField::new('imageName', 'Image')
                ->setBasePath('/images/recipes')
                ->onlyOnIndex(),
            TextField::new('imageFile', 'Image')
                ->setFormType(VichImageType::class)
                ->onlyOnForms(),
            IntegerField::new('difficulty', 'Difficulté'),
            IntegerField::new('preparation_time', 'Temps de préparation (min)'),
            IntegerField::new('cooking_time', 'Temps de cuisson (min)'),
            IntegerField::new('rest_time', 'Temps de repos (min)'),
            AssociationField::new('profile', 'Profil'),
            BooleanField::new('isActive', 'Active'),
            IntegerField::new('nbGuest', 'Nombre de convives'),
            DateTimeField::new('updatedAt', 'Mis à jour le')->hideOnForm(),

            // Collection fields for ingredients and steps
            CollectionField::new('ingredients', 'Ingrédients')
                ->setEntryType(IngredientType::class)
                ->allowAdd()
                ->allowDelete()
                ->setFormTypeOption('by_reference', false),

            CollectionField::new('steps', 'Étapes')
                ->setEntryType(RecipeStepType::class)
                ->allowAdd()
                ->allowDelete()
                ->setFormTypeOption('by_reference', false),
        ];
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Recipe) return;

        // Handle the image upload
        $this->recipeService->handleImageUpload($entityInstance);

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Recipe) return;

        // Handle the image upload
        $this->recipeService->handleImageUpload($entityInstance);

        parent::updateEntity($entityManager, $entityInstance);
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Recipe) return;

        // Handle the image removal
        $this->recipeService->handleImageRemoval($entityInstance);

        parent::deleteEntity($entityManager, $entityInstance);
    }
}