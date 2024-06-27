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

#[Route('/profile/recipes')]
class RecipeController extends AbstractController
{
    private $entityManager;
    private $security;
    private $serializer;
    private $recipeService;

    public function __construct(EntityManagerInterface $entityManager, Security $security, SerializerInterface $serializer, RecipeService $recipeService)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
        $this->serializer = $serializer;
        $this->recipeService = $recipeService;
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
        $user = $this->getUser();
        $profile = $user->getProfile();

        $recipe = new Recipe();
        $recipe->setProfile($profile);

        $form = $this->createForm(RecipeType::class, $recipe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            $this->recipeService->handleImageUpload($recipe);
            $this->entityManager->persist($recipe);
            $this->entityManager->flush();

            $responseData = $this->serializer->serialize($recipe, 'json', ['groups' => 'recipe']);

            return new JsonResponse($responseData, JsonResponse::HTTP_CREATED, [], true);
        }

        $errors = (string) $form->getErrors(true, false);
        return new JsonResponse(['error' => 'Invalid data', 'details' => $errors], JsonResponse::HTTP_BAD_REQUEST);
    }

    #[Route('/{id<\d+>}', name: 'recipe_show', methods: ['GET'])]
    public function show(Recipe $recipe): JsonResponse
    {
        $this->denyAccessUnlessGranted('view', $recipe);

        $data = $this->serializer->serialize($recipe, 'json', ['groups' => 'recipe']);

        return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/{id<\d+>}/edit', name: 'recipe_edit', methods: ['PUT'])]
    public function edit(Request $request, Recipe $recipe): JsonResponse
    {
        $this->denyAccessUnlessGranted('edit', $recipe);

        $form = $this->createForm(RecipeType::class, $recipe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->recipeService->handleImageUpload($recipe);
            $this->entityManager->flush();

            $responseData = $this->serializer->serialize($recipe, 'json', ['groups' => 'recipe']);

            return new JsonResponse($responseData, JsonResponse::HTTP_OK, [], true);
        }

        $errors = (string) $form->getErrors(true, false);
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

        $form = $this->createForm(RecipeType::class, new Recipe());

        return $this->render('recipe/manage.html.twig', [
            'form' => $form->createView(),
            'profile' => $profile,
        ]);
    }
}