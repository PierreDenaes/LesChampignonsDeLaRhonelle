<?php

namespace App\Controller;

use App\Entity\Rating;
use App\Entity\Recipe;
use App\Entity\Comment;
use App\Form\CommentType;
use App\Service\CommentService;
use App\Repository\RecipeRepository;
use App\Repository\SponsorRepository;
use App\Service\RatingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SiteController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private RecipeRepository $recipeRepository;
    private RatingService $ratingService;
    private CommentService $commentService;

    public function __construct(
        EntityManagerInterface $entityManager,
        RecipeRepository $recipeRepository,
        RatingService $ratingService,
        CommentService $commentService
    ) {
        $this->entityManager = $entityManager;
        $this->recipeRepository = $recipeRepository;
        $this->ratingService = $ratingService;
        $this->commentService = $commentService;
    }

    #[Route('/', name: 'app_home')]
    public function index(SponsorRepository $sponsorRepository): Response
    {
        $user = $this->getUser();

        $sponsors = $sponsorRepository->findAll();
        $recipes = $this->recipeRepository->findAll();

        return $this->render('site/index.html.twig', [
            'sponsors' => $sponsors,
            'recipes' => $recipes,
            'user' => $user,
        ]);
    }

    #[Route('/species', name: 'our_species')]
    public function species(): Response
    {
        return $this->render('site/our_species.html.twig');
    }

    #[Route('/recipes', name: 'recipe_all', methods: ['GET'])]
    public function allRecipes(): Response
    {
        $recipes = $this->recipeRepository->findAll();

        return $this->render('site/recipe_public.html.twig', [
            'recipes' => $recipes,
        ]);
    }

    #[Route('/recipe/{id}', name: 'recipe_show_public', methods: ['GET', 'POST'])]
    public function showRecipe(
        AuthenticationUtils $authenticationUtils,
        Request $request,
        Recipe $recipe
    ): Response {
        $user = $this->getUser();
        $userProfile = $user ? $user->getProfile() : null;

        $latestRecipes = $this->recipeRepository->findBy(['isActive' => true], ['updatedAt' => 'DESC'], 5);

        // Utilisation du RatingService
        $ratingData = $this->ratingService->getRatingData($recipe, $userProfile);
        $existingRating = $ratingData['existingRating'];
        $averageRating = $ratingData['averageRating'];
        $ratingCount = $ratingData['ratingCount'];

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        // Gestion des commentaires via le CommentService
        $existingComment = $this->commentService->getExistingComment($userProfile, $recipe);
        $commentForm = $this->commentService->handleCommentForm($request, $userProfile, $recipe);

        if ($commentForm === null && $request->isMethod('POST')) {
            if (!$user) {
                throw new AccessDeniedException('Vous devez être connecté pour ajouter un commentaire.');
            }

            $this->addFlash('success', 'Votre commentaire a été ajouté avec succès.');

            return $this->redirectToRoute('recipe_show_public', ['id' => $recipe->getId()]);
        }

        return $this->render('site/recipe_show.html.twig', [
            'recipe' => $recipe,
            'latestRecipes' => $latestRecipes,
            'last_username' => $lastUsername,
            'error' => $error,
            'existingRating' => $existingRating,
            'averageRating' => $averageRating,
            'ratingCount' => $ratingCount,
            'commentForm' => $commentForm ? $commentForm->createView() : null,
            'existingComment' => $existingComment,
        ]);
    }

    #[Route('/recipe/{id}/rate', name: 'submit_rating', methods: ['POST'])]
    public function submitRating(Request $request, Recipe $recipe): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Vous devez être connecté pour noter cette recette.'], 403);
        }

        $userProfile = $user->getProfile();
        $content = json_decode($request->getContent(), true);
        $newScore = $content['score'] ?? null;

        if ($newScore === null) {
            return new JsonResponse(['error' => 'Note invalide.'], 400);
        }

        // Utiliser le RatingService pour gérer la note
        $existingRating = $this->ratingService->getRatingData($recipe, $userProfile)['existingRating'];

        if ($existingRating) {
            $existingRating->setScore($newScore);
        } else {
            $rating = new Rating();
            $rating->setRecipe($recipe);
            $rating->setProfile($userProfile);
            $rating->setScore($newScore);
            $this->entityManager->persist($rating);
        }

        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Note enregistrée avec succès.', 'newScore' => $newScore]);
    }

    #[Route('/recipe/{id}/current-rating', name: 'get_current_rating', methods: ['GET'])]
    public function getCurrentRating(Recipe $recipe): JsonResponse
    {
        $userProfile = $this->getUser() ? $this->getUser()->getProfile() : null;

        $ratingData = $this->ratingService->getRatingData($recipe, $userProfile);
        $currentRating = $ratingData['existingRating'] ? $ratingData['existingRating']->getScore() : null;

        return new JsonResponse(['currentRating' => $currentRating]);
    }

    #[Route('/recipe/{id}/average-rating', name: 'get_average_rating', methods: ['GET'])]
    public function getAverageRating(Recipe $recipe): JsonResponse
    {
        $ratingData = $this->ratingService->getRatingData($recipe, null);

        return new JsonResponse([
            'averageRating' => $ratingData['averageRating'],
            'ratingCount' => $ratingData['ratingCount'],
        ]);
    }

    #[Route('/comment/{id}/edit-inline', name: 'comment_edit_inline', methods: ['GET'])]
    public function editCommentInline(Comment $comment): JsonResponse
    {
        if ($comment->getAuthor() !== $this->getUser()->getProfile()) {
            throw new AccessDeniedException('Vous ne pouvez pas modifier ce commentaire.');
        }

        $commentForm = $this->createForm(CommentType::class, $comment);

        return new JsonResponse([
            'formHtml' => $this->renderView('site/_edit_comment_form.html.twig', [
                'editCommentForm' => $commentForm->createView(),
                'comment' => $comment,
            ]),
        ]);
    }

    #[Route('/api/check-login', name: 'check_login_status', methods: ['GET'])]
    public function checkLoginStatus(): JsonResponse
    {
        return new JsonResponse(['isUserLoggedIn' => $this->getUser() !== null]);
    }

    #[Route('/comment/{id}/edit-ajax', name: 'comment_edit_ajax', methods: ['POST'])]
    public function editCommentAjax(Request $request, Comment $comment): JsonResponse
    {
        if ($comment->getAuthor() !== $this->getUser()->getProfile()) {
            return new JsonResponse(['error' => 'Vous ne pouvez pas modifier ce commentaire.'], 403);
        }

        $commentForm = $this->createForm(CommentType::class, $comment);
        $commentForm->handleRequest($request);

        if ($commentForm->isSubmitted() && $commentForm->isValid()) {
            $this->entityManager->flush();

            $updatedCommentHtml = $this->renderView('site/_comment_item.html.twig', [
                'comment' => $comment,
                'recipe' => $comment->getRecipe(),
            ]);

            return new JsonResponse([
                'message' => 'Commentaire mis à jour avec succès.',
                'updatedCommentHtml' => $updatedCommentHtml,
            ]);
        }

        return new JsonResponse(['error' => 'Données invalides.'], 400);
    }

    #[Route('/comment/{id}/delete', name: 'comment_delete', methods: ['POST'])]
    public function deleteComment(Comment $comment): Response
    {
        if ($comment->getAuthor() !== $this->getUser()->getProfile()) {
            throw new AccessDeniedException('Vous ne pouvez pas supprimer ce commentaire.');
        }

        $this->entityManager->remove($comment);
        $this->entityManager->flush();

        $this->addFlash('success', 'Votre commentaire a été supprimé avec succès.');

        return $this->redirectToRoute('recipe_show_public', ['id' => $comment->getRecipe()->getId()]);
    }

}