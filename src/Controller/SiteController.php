<?php

namespace App\Controller;

use App\Entity\Rating;
use App\Entity\Recipe;
use App\Entity\Comment;
use App\Form\CommentType;
use Symfony\Component\Mime\Address;
use App\Repository\RatingRepository;
use App\Repository\RecipeRepository;
use App\Repository\SponsorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
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
    private RatingRepository $ratingRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        RecipeRepository $recipeRepository,
        RatingRepository $ratingRepository
    ) {
        $this->entityManager = $entityManager;
        $this->recipeRepository = $recipeRepository;
        $this->ratingRepository = $ratingRepository;
    }

    #[Route('/', name: 'app_home')]
    public function index(SponsorRepository $sponsorRepository): Response
    {
        $user = $this->getUser(); // Conservation de la variable $user

        $sponsors = $sponsorRepository->findAll();
        $recipes = $this->recipeRepository->findAll();

        return $this->render('site/index.html.twig', [
            'sponsors' => $sponsors,
            'recipes' => $recipes,
            'user' => $user, // Passage de $user à la vue
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
        MailerInterface $mailer,
        Recipe $recipe
    ): Response {
        $user = $this->getUser();
        $userProfile = $user ? $user->getProfile() : null;

        $latestRecipes = $this->recipeRepository->findBy(['isActive' => true], ['updatedAt' => 'DESC'], 5);

        $existingRating = $userProfile ? $this->ratingRepository->findOneBy([
            'recipe' => $recipe,
            'profile' => $userProfile,
        ]) : null;

        $averageData = $this->calculateAverageRating($recipe);

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        $commentForm = null;
        $existingComment = null;

        if ($userProfile) {
            $existingComment = $this->entityManager->getRepository(Comment::class)->findOneBy([
                'recipe' => $recipe,
                'author' => $userProfile,
            ]);
        }

        if (!$existingComment) {
            $comment = new Comment();
            $commentForm = $this->createForm(CommentType::class, $comment);
            $commentForm->handleRequest($request);

            if ($commentForm->isSubmitted() && $commentForm->isValid()) {
                if (!$user) {
                    throw new AccessDeniedException('Vous devez être connecté pour ajouter un commentaire.');
                }

                $comment->setRecipe($recipe);
                $comment->setAuthor($userProfile);

                $this->entityManager->persist($comment);
                $this->entityManager->flush();

                $this->sendNewCommentNotification($mailer, $recipe);

                $this->addFlash('success', 'Votre commentaire a été ajouté avec succès.');

                return $this->redirectToRoute('recipe_show_public', ['id' => $recipe->getId()]);
            }
        }

        return $this->render('site/recipe_show.html.twig', [
            'recipe' => $recipe,
            'latestRecipes' => $latestRecipes,
            'last_username' => $lastUsername,
            'error' => $error,
            'existingRating' => $existingRating,
            'averageRating' => $averageData['averageRating'],
            'ratingCount' => $averageData['ratingCount'],
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

        $existingRating = $this->ratingRepository->findOneBy([
            'recipe' => $recipe,
            'profile' => $userProfile,
        ]);

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

        if (!$userProfile) {
            return new JsonResponse(['currentRating' => null]);
        }

        $existingRating = $this->ratingRepository->findOneBy([
            'recipe' => $recipe,
            'profile' => $userProfile,
        ]);

        $currentRating = $existingRating ? $existingRating->getScore() : null;

        return new JsonResponse(['currentRating' => $currentRating]);
    }

    #[Route('/recipe/{id}/average-rating', name: 'get_average_rating', methods: ['GET'])]
    public function getAverageRating(Recipe $recipe): JsonResponse
    {
        $averageData = $this->calculateAverageRating($recipe);

        return new JsonResponse($averageData);
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

    /**
     * Envoie un email de notification à l'auteur de la recette pour un nouveau commentaire.
     */
    private function sendNewCommentNotification(MailerInterface $mailer, Recipe $recipe): void
    {
        $author = $recipe->getProfile();
        if (!$author || !$author->getIdUser()->getEmail()) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from(new Address('no-reply@monsite.com', 'Les Champignons de La Rhonelle'))
            ->to(new Address($author->getIdUser()->getEmail(), $author->getFirstname()))
            ->subject('Nouveau commentaire sur votre recette')
            ->htmlTemplate('emails/new_comment_notification.html.twig')
            ->context([
                'recipe' => $recipe,
                'author' => $author,
            ]);

        $mailer->send($email);
    }

    /**
     * Calcule la note moyenne d'une recette.
     */
    private function calculateAverageRating(Recipe $recipe): array
    {
        $ratings = $this->ratingRepository->findBy(['recipe' => $recipe]);
        $ratingCount = count($ratings);

        if ($ratingCount > 0) {
            $totalScore = array_sum(array_map(fn($r) => $r->getScore(), $ratings));
            $averageRating = round($totalScore / $ratingCount * 2) / 2;
        } else {
            $averageRating = null;
        }

        return ['averageRating' => $averageRating, 'ratingCount' => $ratingCount];
    }
}