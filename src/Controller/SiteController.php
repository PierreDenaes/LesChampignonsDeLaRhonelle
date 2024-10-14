<?php

namespace App\Controller;

use App\Entity\Rating;
use App\Entity\Recipe;
use App\Entity\Comment;
use App\Form\RatingType;
use App\Form\CommentType;
use App\Repository\RatingRepository;
use App\Repository\RecipeRepository;
use App\Repository\SponsorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SiteController extends AbstractController
{
    private $serializer;
    private $entityManager;

    public function __construct(SerializerInterface $serializer, EntityManagerInterface $entityManager)
    {
        $this->serializer = $serializer;
        $this->entityManager = $entityManager;
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
    #[Route('/recipe/{id}', name: 'recipe_show_public', methods: ['GET', 'POST'])]
    public function showRecipe(AuthenticationUtils $authenticationUtils, RecipeRepository $recipeRepository, RatingRepository $ratingRepository,Request $request, $id): Response
    {
        $recipe = $recipeRepository->find($id);
        // Obtenir l'utilisateur actuel et son profil
        $user = $this->getUser();
        $userProfile = $user ? $user->getProfile() : null;

        // Récupérer les dernières recettes, triées par date de mise à jour
        $latestRecipes = $recipeRepository->findBy(['isActive' => true], ['updatedAt' => 'DESC'], 5);

        // Obtenir l'utilisateur actuel
        $userProfile = $this->getUser() ? $this->getUser()->getProfile() : null;

        // Chercher la note existante de l'utilisateur pour la recette
        $existingRating = $userProfile ? $ratingRepository->findOneBy([
            'recipe' => $recipe,
            'profile' => $userProfile,
        ]) : null;

        // Calculer la note moyenne de la recette
        $ratings = $ratingRepository->findBy(['recipe' => $recipe]);
        $ratingCount = count($ratings);
        $averageRating = $ratingCount > 0 ? round(array_sum(array_map(fn($r) => $r->getScore(), $ratings)) / $ratingCount * 2) / 2 : null;

        // Obtenir l'erreur et le dernier nom d'utilisateur pour la modale de connexion
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        // Gestion des commentaires
        $comment = new Comment();
        $commentForm = $this->createForm(CommentType::class, $comment);
        $commentForm->handleRequest($request);

        if ($commentForm->isSubmitted() && $commentForm->isValid()) {
            if (!$user) {
                throw new AccessDeniedException('Vous devez être connecté pour ajouter un commentaire.');
            }
    
            // Relier le commentaire à la recette et à l'auteur (le profil de l'utilisateur connecté)
            $comment->setRecipe($recipe);
            $comment->setAuthor($userProfile);
    
            $this->entityManager->persist($comment);
            $this->entityManager->flush();
    
            $this->addFlash('success', 'Votre commentaire a été ajouté avec succès.');
            
            return $this->redirectToRoute('recipe_show_public', ['id' => $recipe->getId()]);
        }

        // Renvoyer à la vue
            return $this->render('site/recipe_show.html.twig', [
                'recipe' => $recipe,
                'latestRecipes' => $latestRecipes,
                'last_username' => $lastUsername,
                'error' => $error,
                'existingRating' => $existingRating,
                'averageRating' => $averageRating,
                'ratingCount' => $ratingCount,
                'commentForm' => $commentForm->createView(),  // Formulaire de commentaire
            ]);
    }
    #[Route('/recipe/{id}/rate', name: 'submit_rating', methods: ['POST'])]
    public function submitRating(Request $request, Recipe $recipe, EntityManagerInterface $entityManager, RatingRepository $ratingRepository): Response
    {
        // Vérifier si l'utilisateur est connecté
        if (!$this->getUser()) {
            return new JsonResponse(['error' => 'Vous devez être connecté pour noter cette recette.'], 403);
        }

        $userProfile = $this->getUser()->getProfile();
        $content = json_decode($request->getContent(), true);
        $newScore = $content['score'] ?? null;

        if ($newScore === null) {
            return new JsonResponse(['error' => 'Note invalide.'], 400);
        }

        // Chercher s'il y a déjà une note pour cette recette et cet utilisateur
        $existingRating = $ratingRepository->findOneBy([
            'recipe' => $recipe,
            'profile' => $userProfile,
        ]);

        if ($existingRating) {
            // Mise à jour de la note existante
            $existingRating->setScore($newScore);
            $entityManager->flush();
            return new JsonResponse(['message' => 'Note mise à jour avec succès.']);
        } else {
            // Création d'une nouvelle note
            $rating = new Rating();
            $rating->setRecipe($recipe);
            $rating->setProfile($userProfile);
            $rating->setScore($newScore);

            $entityManager->persist($rating);
            $entityManager->flush();
            return new JsonResponse(['message' => 'Note enregistrée avec succès.',
            'newScore' => $newScore
            ]);
        }
    }
    #[Route('/recipe/{id}/current-rating', name: 'get_current_rating', methods: ['GET'])]
    public function getCurrentRating(RatingRepository $ratingRepository, Recipe $recipe): JsonResponse
    {
        $userProfile = $this->getUser() ? $this->getUser()->getProfile() : null;

        // Si l'utilisateur n'est pas connecté, on retourne une réponse vide
        if (!$userProfile) {
            return new JsonResponse(['currentRating' => null]);
        }

        // Chercher la note existante de l'utilisateur pour la recette
        $existingRating = $ratingRepository->findOneBy([
            'recipe' => $recipe,
            'profile' => $userProfile,
        ]);

        $currentRating = $existingRating ? $existingRating->getScore() : null;

        return new JsonResponse(['currentRating' => $currentRating]);
    }
    #[Route('/recipe/{id}/average-rating', name: 'get_average_rating', methods: ['GET'])]
    public function getAverageRating(RatingRepository $ratingRepository, Recipe $recipe): JsonResponse
    {
        // Récupérer toutes les notes pour la recette
        $ratings = $ratingRepository->findBy(['recipe' => $recipe]);

        // Calculer la moyenne
        $total = 0;
        $count = count($ratings);

        if ($count > 0) {
            foreach ($ratings as $rating) {
                $total += $rating->getScore();
            }
            $averageRating = round($total / $count * 2) / 2; // Arrondir à la demi-étape
        } else {
            $averageRating = null; // Pas encore de notes
        }

        return new JsonResponse(['averageRating' => $averageRating, 'ratingCount' => $count]);
    }
    #[Route('/api/check-login', name: 'check_login_status', methods: ['GET'])]
    public function checkLoginStatus(): JsonResponse
    {
        return new JsonResponse(['isUserLoggedIn' => $this->getUser() !== null]);
    }

}
