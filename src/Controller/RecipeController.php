<?php

namespace App\Controller;

use App\Entity\Recipe;
use App\Form\RecipeType;
use App\Service\RecipeService;
use Symfony\Component\Mime\Address;
use App\Repository\RecipeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/profile/recipes')]
class RecipeController extends AbstractController
{
    private $entityManager;
    private $security;
    private $serializer;
    private $recipeService;
    private $mailer;
    private $adminEmail;

    public function __construct(EntityManagerInterface $entityManager, Security $security, SerializerInterface $serializer, RecipeService $recipeService, MailerInterface $mailer, string $adminEmail)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
        $this->serializer = $serializer;
        $this->recipeService = $recipeService;
        $this->mailer = $mailer;
        $this->adminEmail = $adminEmail;
    }

    #[Route('', name: 'recipe_index', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function index(RecipeRepository $recipeRepository): JsonResponse
    {
        $user = $this->getUser();
        $profile = $user->getProfile();

        $recipes = $recipeRepository->findBy(['profile' => $profile]);

        $data = $this->serializer->serialize($recipes, 'json', ['groups' => 'recipe']);

        return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/new', name: 'recipe_new', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function new(Request $request, UrlGeneratorInterface $urlGenerator): JsonResponse
    {
        
        
        $user = $this->getUser();
        $profile = $user->getProfile();

        $recipe = new Recipe();
        $recipe->setProfile($profile);
        $recipe->setIsActive(false);

        $form = $this->createForm(RecipeType::class, $recipe);
    
        $form->handleRequest($request);


        if ($form->isSubmitted() && !$form->isValid()) {
            
            $errors = [];
        
            // Collecter les erreurs globales (hors formulaires imbriqués)
            foreach ($form->getErrors(true) as $error) {
                $origin = $error->getOrigin();
                if ($origin !== null) {
                    $field = (string)$origin->getPropertyPath();
                    
                    // Vérifier si l'erreur n'appartient pas aux sous-formulaires imbriqués pour éviter les doublons
                    if (!preg_match('/^ingredients\[\d+\]|steps\[\d+\]/', $field)) {
                        $errors[$field][] = $error->getMessage();
                    }
                } else {
                    // Ajouter les erreurs globales si aucune origine n'est trouvée
                    $errors['global'][] = $error->getMessage();
                }
            }
        
            // Collecter les erreurs des ingrédients
            foreach ($form->get('ingredients') as $index => $ingredientForm) {
                foreach ($ingredientForm->getErrors(true) as $error) {
                    $origin = $error->getOrigin();
                    if ($origin !== null) {
                        $field = 'ingredients[' . $index . '].' . $origin->getName();
                        $errors[$field][] = $error->getMessage();
                    }
                }
            }
        
            // Collecter les erreurs des étapes
            foreach ($form->get('steps') as $index => $stepForm) {
                foreach ($stepForm->getErrors(true) as $error) {
                    $origin = $error->getOrigin();
                    if ($origin !== null) {
                        $field = 'steps[' . $index . '].' . $origin->getName();
                        $errors[$field][] = $error->getMessage();
                    }
                }
            }
        
            // Renvoyer une réponse JSON avec les erreurs
            return new JsonResponse([
                'success' => false,
                'errors' => $errors,
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
        if ($form->isSubmitted() && $form->isValid()) {
            $this->recipeService->handleImageUpload($recipe);
            $this->entityManager->persist($recipe);
            $this->entityManager->flush();

            // Générer l'URL de la recette dans l'admin
            $adminRecipeUrl = $urlGenerator->generate('admin', [
                'crudAction' => 'edit',  // Action (edit, show, delete, etc.)
                'crudControllerFqcn' => 'App\Controller\Admin\RecipeCrudController',  // Controller FQCN
                'entityId' => $recipe->getId(),  // ID de la recette
            ], UrlGeneratorInterface::ABSOLUTE_URL);
            // Envoyer un email à l'administrateur
            $adminEmail = (new TemplatedEmail())
                ->from(new Address('admin@leschampignonsdelarhonelle.com', 'Les Champignons de La Rhonelle'))
                ->to($this->adminEmail)
                ->subject('Nouvelle recette ajoutée')
                ->htmlTemplate('recipe/admin_notification_email.html.twig')
                ->context([
                    'title' => $recipe->getTitle(),
                    'description' => $recipe->getDescription(),
                    'profile_name' => $profile->getName(),
                    'admin_recipe_url' => $adminRecipeUrl,  // URL de la recette
                ]);

            $this->mailer->send($adminEmail);

            // Envoyer un email de confirmation à l'utilisateur
            $userEmail = (new TemplatedEmail())
                ->from(new Address('admin@leschampignonsdelarhonelle.com', 'Les Champignons de La Rhonelle'))
                ->to($user->getEmail())
                ->subject('Confirmation de l\'ajout de votre recette')
                ->htmlTemplate('recipe/user_confirmation_email.html.twig')
                ->context([
                    'title' => $recipe->getTitle(),
                    'profile_name' => $profile->getName(),
                ]);

            $this->mailer->send($userEmail);

            $responseData = $this->serializer->serialize($recipe, 'json', ['groups' => 'recipe']);
            return new JsonResponse([
                'success' => true,
                'message' => '🎉 Félicitations ! Votre recette a été ajoutée avec succès et sera modérée dans un délai de 48 heures maximum. Merci beaucoup pour votre précieuse contribution ! 🎉',
                'data' => json_decode($responseData, true)
            ], JsonResponse::HTTP_CREATED);
        }

        return new JsonResponse(['error' => 'Invalid data'], JsonResponse::HTTP_BAD_REQUEST);
    }

    #[Route('/{id<\d+>}/show', name: 'recipe_show', methods: ['GET'])]
    public function show(Recipe $recipe): JsonResponse
    {
        $this->denyAccessUnlessGranted('view', $recipe);

        $data = $this->serializer->serialize($recipe, 'json', ['groups' => 'recipe']);

        return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/{id<\d+>}/edit', name: 'recipe_edit', methods: ['POST', 'PUT'])]
    public function edit(Request $request, Recipe $recipe): JsonResponse
    {
        $this->denyAccessUnlessGranted('edit', $recipe);

        // Stocker les étapes actuelles
        $existingSteps = $recipe->getSteps()->toArray();

        $form = $this->createForm(RecipeType::class, $recipe, [
            'method' => 'POST',
            'attr' => ['enctype' => 'multipart/form-data']
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && !$form->isValid()) {
            $errors = [];
        
            // Collecter les erreurs globales
            foreach ($form->getErrors(true) as $error) {
                $field = $error->getOrigin()->getName();
                $errors[$field][] = $error->getMessage();
            }
        
            // Collecter les erreurs pour chaque ingrédient
            foreach ($form->get('ingredients') as $index => $ingredientForm) {
                foreach ($ingredientForm->getErrors(true) as $error) {
                    $field = 'ingredients[' . $index . '].' . $error->getOrigin()->getName();
                    $errors[$field][] = $error->getMessage();
                }
            }
        
            // Collecter les erreurs pour chaque étape
            foreach ($form->get('steps') as $index => $stepForm) {
                foreach ($stepForm->getErrors(true) as $error) {
                    $field = 'steps[' . $index . '].' . $error->getOrigin()->getName();
                    $errors[$field][] = $error->getMessage();
                }
            }
        
            // Renvoyer une réponse JSON avec les erreurs
            return new JsonResponse([
                'success' => false,
                'errors' => $errors
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
        if ($form->isSubmitted() && $form->isValid()) {
            $this->recipeService->handleImageUpload($recipe);

            // Suppression des étapes qui ne sont plus présentes dans le formulaire
            foreach ($existingSteps as $existingStep) {
                if (!$recipe->getSteps()->contains($existingStep)) {
                    $this->entityManager->remove($existingStep);
                }
            }

            $this->entityManager->flush();

            $responseData = $this->serializer->serialize($recipe, 'json', ['groups' => 'recipe']);

            return new JsonResponse([
                'success' => true,
                'message' => '🎉 Félicitations ! Votre recette a été modifiée avec succès et sera modérée dans un délai de 48 heures maximum. Merci beaucoup pour votre précieuse contribution ! 🎉',
                'data' => json_decode($responseData, true)
            ], JsonResponse::HTTP_OK);
        }

        return new JsonResponse(['error' => 'Invalid data'], JsonResponse::HTTP_BAD_REQUEST);
    }

    #[Route('/{id<\d+>}/edit-form', name: 'recipe_edit_form', methods: ['GET'])]
    public function editForm(Recipe $recipe): Response
    {
        $this->denyAccessUnlessGranted('edit', $recipe);


        $form = $this->createForm(RecipeType::class, $recipe);

        return $this->render('recipe/edit.html.twig', [
            'form' => $form->createView(),
            'recipe' => $recipe,

        ]);
    }

    #[Route('/{id<\d+>}', name: 'recipe_delete', methods: ['DELETE'])]
    public function delete(Request $request, Recipe $recipe): JsonResponse
    {
        $this->denyAccessUnlessGranted('delete', $recipe);

        // Récupérer les données de la requête
        $data = json_decode($request->getContent(), true);
        $imageName = $data['imageName'] ?? null;

        // Si l'image n'est pas l'image par défaut, passer le nom de l'image au service pour la gestion de la suppression
        if ($imageName) {
            $recipe->setImageName($imageName);
            $this->recipeService->handleImageRemoval($recipe);
        }

        $this->entityManager->remove($recipe);
        $this->entityManager->flush();

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    #[Route('/manage', name: 'recipe_manage', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function manage(): Response
    {
        $user = $this->getUser();
        $profile = $user->getProfile();

        $form = $this->createForm(RecipeType::class, new Recipe());

        return $this->render('recipe/manage.html.twig', [
            'form' => $form->createView(),
            'profile' => $profile,
        ]);
    }
}
