<?php

namespace App\Controller;

use App\Entity\Rating;
use App\Entity\Recipe;
use App\Form\RatingType;
use App\Repository\RecipeRepository;
use App\Repository\SponsorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

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
    public function showRecipe(AuthenticationUtils $authenticationUtils, RecipeRepository $recipeRepository, $id): Response
    {
        $recipe = $recipeRepository->find($id);

        // Récupérer les dernières recettes, triées par date de mise à jour
        $latestRecipes = $recipeRepository->findBy(['isActive' => true], ['updatedAt' => 'DESC'], 5);

        // Obtenir l'erreur et le dernier nom d'utilisateur pour la modale de connexion
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
        return $this->render('site/recipe_show.html.twig', [
            'recipe' => $recipe,
            'latestRecipes' => $latestRecipes,
            'last_username' => $lastUsername,  // Ajouté pour la modale de connexion
            'error' => $error,  
        ]);
    }
    #[Route('/recipe/{id}/rate', name: 'submit_rating', methods: ['POST'])]
    public function submitRating(Request $request, Recipe $recipe, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur est connecté
        if (!$this->getUser()) {
            $this->addFlash('error', 'Vous devez être connecté pour noter cette recette. Veuillez vous connecter ou vous inscrire.');
            return $this->redirectToRoute('app_login'); // Ou une autre route vers la page de connexion
        }

        $rating = new Rating();
        $ratingForm = $this->createForm(RatingType::class, $rating);
        $ratingForm->handleRequest($request);

        if ($ratingForm->isSubmitted() && $ratingForm->isValid()) {
            // Associer la recette et l'utilisateur actuel
            $rating->setRecipe($recipe);
            $rating->setProfile($this->getUser()->getProfile());
            $entityManager->persist($rating);
            $entityManager->flush();

            return $this->redirectToRoute('recipe_show', ['id' => $recipe->getId()]);
        }

        return $this->render('recipe_show.html.twig', [
            'recipe' => $recipe,
            'ratingForm' => $ratingForm->createView(),
            'isUserLoggedIn' => $this->getUser() !== null,
        ]);
    }
    #[Route('/api/check-login', name: 'check_login_status', methods: ['GET'])]
    public function checkLoginStatus(): JsonResponse
    {
        return new JsonResponse(['isUserLoggedIn' => $this->getUser() !== null]);
    }

}
