<?php

namespace App\Controller;

use App\Repository\SponsorRepository;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\RecipeRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class SiteController extends AbstractController
{
    private $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }
    
    #[Route('/', name: 'app_home')]
    public function index(SponsorRepository $sponsorRepository, RecipeRepository $recipeRepository): Response
    {
        $user = $this->getUser();
       
        $sponsors = $sponsorRepository->findAll();
        $recipes = $recipeRepository->findAll();
        return $this->render('site/index.html.twig', [
            'controller_name' => 'HomeController',
            'sponsors' => $sponsors,
            'recipes' => $recipes,
            'user' => $user,
        ]);
    }

    #[Route('/species', name: 'our_species')]
    public function species(): Response
    {
        return $this->render('site/our_species.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    #[Route('/recipes', name: 'recipe_all', methods: ['GET'])]
    public function allRecipes(RecipeRepository $recipeRepository): Response
    {
        $recipes = $recipeRepository->findAll();

        return $this->render('site/recipe_public.html.twig', [
            'recipes' => $recipes,
        ]);
    }
    #[Route('/recipe/{id}', name: 'recipe_show_public', methods: ['GET'])]
    public function showRecipe(RecipeRepository $recipeRepository, $id): Response
    {
        $recipe = $recipeRepository->find($id);

        // Récupérer les dernières recettes, triées par date de mise à jour
        $latestRecipes = $recipeRepository->findBy(['isActive' => true], ['updatedAt' => 'DESC'], 5);

        return $this->render('site/recipe_show.html.twig', [
            'recipe' => $recipe,
            'latestRecipes' => $latestRecipes,
        ]);
    }

}
