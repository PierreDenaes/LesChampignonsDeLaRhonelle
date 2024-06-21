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
    public function index(SponsorRepository $sponsorRepository): Response
    {
        $user = $this->getUser();
       
        $sponsors = $sponsorRepository->findAll();
        return $this->render('site/index.html.twig', [
            'controller_name' => 'HomeController',
            'sponsors' => $sponsors,
            'user' => $user,
        ]);
    }
    #[Route('/recipes', name: 'recipe_all', methods: ['GET'])]
    public function allRecipes(RecipeRepository $recipeRepository): JsonResponse
    {
        $recipes = $recipeRepository->findAll();

        $data = $this->serializer->serialize($recipes, 'json', ['groups' => 'recipe']);

        return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
    }
}
