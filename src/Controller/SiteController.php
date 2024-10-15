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
    public function showRecipe(AuthenticationUtils $authenticationUtils, RecipeRepository $recipeRepository, RatingRepository $ratingRepository, Request $request,MailerInterface $mailer, $id): Response
    {
        $recipe = $recipeRepository->find($id);
        // Obtenir l'utilisateur actuel et son profil
        $user = $this->getUser();
        $userProfile = $user ? $user->getProfile() : null;

        // Récupérer les dernières recettes, triées par date de mise à jour
        $latestRecipes = $recipeRepository->findBy(['isActive' => true], ['updatedAt' => 'DESC'], 5);

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
        // Vérification s'il existe déjà un commentaire pour cet utilisateur et cette recette
        $existingComment = $userProfile ? $this->entityManager->getRepository(Comment::class)->findOneBy([
            'recipe' => $recipe,
            'author' => $userProfile,
        ]) : null;

        $commentForm = null;
        if (!$existingComment) {
            // Si l'utilisateur n'a pas encore commenté, on affiche le formulaire
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
        
                // Envoi de l'email à l'auteur de la recette
                $this->sendNewCommentNotification($mailer, $recipe);
        
                $this->addFlash('success', 'Votre commentaire a été ajouté avec succès.');
        
                return $this->redirectToRoute('recipe_show_public', ['id' => $recipe->getId()]);
            }
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
            'commentForm' => $commentForm ? $commentForm->createView() : null,  // Formulaire de commentaire ou null
            'existingComment' => $existingComment, // Passer l'information du commentaire existant
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
    #[Route('/comment/{id}/edit-inline', name: 'comment_edit_inline', methods: ['GET'])]
    public function editCommentInline(Request $request, Comment $comment): JsonResponse
    {
        // Vérification si l'utilisateur est bien le propriétaire du commentaire
        if ($comment->getAuthor() !== $this->getUser()->getProfile()) {
            throw new AccessDeniedException('Vous ne pouvez pas modifier ce commentaire.');
        }

        // Créer le formulaire de modification
        $commentForm = $this->createForm(CommentType::class, $comment);
        
        // Renvoyer le HTML du formulaire de modification
        return new JsonResponse([
            'formHtml' => $this->renderView('site/_edit_comment_form.html.twig', [
                'editCommentForm' => $commentForm->createView(),
                'comment' => $comment
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
        // Vérification si l'utilisateur est bien le propriétaire du commentaire
        if ($comment->getAuthor() !== $this->getUser()->getProfile()) {
            return new JsonResponse(['error' => 'Vous ne pouvez pas modifier ce commentaire.'], 403);
        }

        // Traiter le formulaire de modification
        $commentForm = $this->createForm(CommentType::class, $comment);
        $commentForm->handleRequest($request);

        if ($commentForm->isSubmitted() && $commentForm->isValid()) {
            $this->entityManager->flush();

            // Renvoyer l'intégralité du commentaire mis à jour avec le HTML
            $updatedCommentHtml = $this->renderView('site/_comment_item.html.twig', [
                'comment' => $comment,
                'recipe' => $comment->getRecipe(),
            ]);

            return new JsonResponse([
                'message' => 'Commentaire mis à jour avec succès.',
                'updatedCommentHtml' => $updatedCommentHtml,
            ]);
        }

        // Retourner les erreurs s'il y a des erreurs de validation
        return new JsonResponse([
            'error' => 'Données invalides.',
        ], 400);
    }
    #[Route('/comment/{id}/delete', name: 'comment_delete', methods: ['POST'])]
    public function deleteComment(Request $request, Comment $comment): Response
    {
        // Vérification si l'utilisateur est bien le propriétaire du commentaire
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
    private function sendNewCommentNotification(MailerInterface $mailer, Recipe $recipe)
    {
        // Vérifier si l'auteur de la recette a un email valide
        $author = $recipe->getProfile();
        if (!$author || !$author->getIdUser()->getEmail()) {
            return;
        }

        // Créer l'email de notification
        $email = (new TemplatedEmail())
            ->from(new Address('no-reply@monsite.com', 'Les Champignons de La Rhonelle'))
            ->to(new Address($author->getIdUser()->getEmail(), $author->getFirstname()))
            ->subject('Nouveau commentaire sur votre recette')
            ->htmlTemplate('emails/new_comment_notification.html.twig') // Template d'email
            ->context([
                'recipe' => $recipe,
                'author' => $author,
            ]);

        // Envoyer l'email
        $mailer->send($email);
    }

}
