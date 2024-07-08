<?php
namespace App\Controller\Admin;

use App\Entity\Recipe;
use App\Form\IngredientType;
use App\Form\RecipeStepType;
use App\Service\RecipeService;
use Symfony\Component\Mime\Address;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Vich\UploaderBundle\Form\Type\VichImageType;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class RecipeCrudController extends AbstractCrudController
{
    private $recipeService;
    private $entityManager;
    private $mailer;
    private $router;

    public function __construct(RecipeService $recipeService, EntityManagerInterface $entityManager, MailerInterface $mailer, UrlGeneratorInterface $router)
    {
        $this->recipeService = $recipeService;
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
        $this->router = $router;
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
            BooleanField::new('isActive', 'Active')->setRequired(true),
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

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Recipe) return;

        // Check if the entity was active before and now is inactive
        $oldRecipe = $entityManager->getUnitOfWork()->getOriginalEntityData($entityInstance);
        $wasActive = $oldRecipe['isActive'] ?? false;
        $isActive = $entityInstance->isIsActive();

        // Handle the image upload
        $this->recipeService->handleImageUpload($entityInstance);

        parent::updateEntity($entityManager, $entityInstance);

        // Send email if the recipe has just been activated
        if (!$wasActive && $isActive) {
            $user = $entityInstance->getProfile()->getIdUser();
            $recipeUrl = $this->router->generate('recipe_show_public', ['id' => $entityInstance->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
            $userEmail = (new TemplatedEmail())
                ->from(new Address('contact@leschampignonsdelarhonelle.com', 'Les Champignons de La Rhonelle'))
                ->to($user->getEmail())
                ->subject('Votre recette est validée')
                ->htmlTemplate('recipe/user_recipe_approved_email.html.twig')
                ->context([
                    'recipe' => $entityInstance,
                    'profile_name' => $entityInstance->getProfile()->getName(),
                    'recipe_url' => $recipeUrl,
                ]);

            $this->mailer->send($userEmail);
        }
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Recipe) return;

        // Handle the image upload
        $this->recipeService->handleImageUpload($entityInstance);

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Recipe) return;

        // Handle the image removal
        $this->recipeService->handleImageRemoval($entityInstance);

        parent::deleteEntity($entityManager, $entityInstance);
    }
}