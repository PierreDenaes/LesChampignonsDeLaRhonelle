<?php

namespace App\Controller;

use App\Entity\Recipe;
use App\Form\RecipeType;
use App\Service\RecipeService;
use App\Repository\RecipeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Psr\Log\LoggerInterface;

#[Route('/profile/recipes')]
class RecipeController extends AbstractController
{
    private $entityManager;
    private $security;
    private $serializer;
    private $recipeService;
    private $logger;

    public function __construct(EntityManagerInterface $entityManager, Security $security, SerializerInterface $serializer, RecipeService $recipeService, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
        $this->serializer = $serializer;
        $this->recipeService = $recipeService;
        $this->logger = $logger;
    }

    #[Route('', name: 'recipe_index', methods: ['GET'])]
    public function index(RecipeRepository $recipeRepository): JsonResponse
    {
        $user = $this->getUser();
        $profile = $user->getProfile();

        $recipes = $recipeRepository->findBy(['profile' => $profile]);

        $data = $this->serializer->serialize($recipes, 'json', ['groups' => 'recipe']);

        return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/new', name: 'recipe_new', methods: ['POST'])]
    public function new(Request $request): JsonResponse
    {
        $this->logger->info('Entering new recipe creation method');

        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        $profile = $user->getProfile();

        $recipe = new Recipe();
        $recipe->setProfile($profile);

        $form = $this->createForm(RecipeType::class, $recipe);
        
        $this->logger->debug('Request content: ' . $request->getContent());
        
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->logger->info('Form submitted');
            if ($form->isValid()) {
                $this->logger->info('Form is valid');
                try {
                    $this->entityManager->beginTransaction();
                    $this->recipeService->handleImageUpload($recipe);
                    $this->entityManager->persist($recipe);
                    $this->entityManager->flush();
                    $this->entityManager->commit();

                    $responseData = $this->serializer->serialize($recipe, 'json', ['groups' => 'recipe']);
                    return new JsonResponse($responseData, JsonResponse::HTTP_CREATED, [], true);
                } catch (\Exception $e) {
                    $this->entityManager->rollback();
                    $this->logger->error('Error creating recipe: ' . $e->getMessage());
                    return new JsonResponse(['error' => 'An error occurred while creating the recipe'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
                }
            } else {
                $this->logger->warning('Form is invalid');
                $errors = $form->getErrors(true, false);
                foreach ($errors as $error) {
                    $this->logger->warning($error->getMessage());
                }
            }
        } else {
            $this->logger->warning('Form not submitted');
        }

        $errors = (string) $form->getErrors(true, false);
        $this->logger->error('Form errors: ' . $errors);
        return new JsonResponse(['error' => 'Invalid data', 'details' => $errors], JsonResponse::HTTP_BAD_REQUEST);
    }
    #[Route('/{id<\d+>}', name: 'recipe_show', methods: ['GET'])]
    public function show(Recipe $recipe): JsonResponse
    {
        $this->denyAccessUnlessGranted('view', $recipe);

        $data = $this->serializer->serialize($recipe, 'json', ['groups' => 'recipe']);

        return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/{id<\d+>}/edit/view', name: 'recipe_edit_view', methods: ['GET'])]
    public function editView(RecipeRepository $recipeRepository, $id): Response
    {
        $recipe = $recipeRepository->findRecipeWithDetails($id);

        if (!$recipe) {
            throw $this->createNotFoundException('Recipe not found');
        }

        $this->denyAccessUnlessGranted('edit', $recipe);

        $user = $this->getUser();
        $profile = $user->getProfile();

        $form = $this->createForm(RecipeType::class, $recipe);

        return $this->render('recipe/manage.html.twig', [
            'form_new' => $form->createView(),
            'form_edit' => $form->createView(),
            'profile' => $profile,
            'recipe' => $recipe,
        ]);
    }

    #[Route('/{id<\d+>}/edit', name: 'recipe_edit', methods: ['PUT', 'PATCH'])]
    public function edit(Request $request, RecipeRepository $recipeRepository, $id): JsonResponse
    {
        $recipe = $recipeRepository->findRecipeWithDetails($id);

        if (!$recipe) {
            return new JsonResponse(['error' => 'Recipe not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted('edit', $recipe);

        $form = $this->createForm(RecipeType::class, $recipe);
        $form->handleRequest($request);

        // Log the request data
        $this->logger->info('Request data: ' . $request->getContent());

        if ($form->isSubmitted() && $form->isValid()) {
            $this->recipeService->handleImageUpload($recipe);
            $this->entityManager->flush();

            $responseData = $this->serializer->serialize($recipe, 'json', ['groups' => 'recipe']);

            return new JsonResponse($responseData, JsonResponse::HTTP_OK, [], true);
        }

        // Log form errors
        $errors = (string) $form->getErrors(true, false);
        $this->logger->error('Form errors: ' . $errors);

        return new JsonResponse(['error' => 'Invalid data', 'details' => $errors], JsonResponse::HTTP_BAD_REQUEST);
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
    public function manage(): Response
    {
        $user = $this->getUser();
        $profile = $user->getProfile();

        $formNew = $this->createForm(RecipeType::class, new Recipe());
        $formEdit = $this->createForm(RecipeType::class, new Recipe());

        return $this->render('recipe/manage.html.twig', [
            'form_new' => $formNew->createView(),
            'form_edit' => $formEdit->createView(),
            'profile' => $profile,
        ]);
    }
}