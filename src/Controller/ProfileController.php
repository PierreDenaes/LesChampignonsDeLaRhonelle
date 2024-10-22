<?php

namespace App\Controller;

use App\Entity\Profile;
use App\Form\ProfileType;
use App\Service\AvatarService;
use App\Repository\RecipeRepository;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @IsGranted("ROLE_USER")
 */
#[Route('/profile')]
class ProfileController extends AbstractController
{
    private $entityManager;
    private $security;
    private $avatarService;
    private $csrfTokenManager;

    public function __construct(EntityManagerInterface $entityManager, Security $security, AvatarService $avatarService, CsrfTokenManagerInterface $csrfTokenManager)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
        $this->avatarService = $avatarService;
        $this->csrfTokenManager = $csrfTokenManager;
    }

    #[Route('/', name: 'app_profile')]
    public function index(Request $request): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $profile = $user->getProfile();

        if (!$profile) {
            // Rediriger vers la page de création de profil si aucun profil n'existe
            return $this->redirectToRoute('profile_home');
        }

        // Si le profil existe, on gère l'édition du profil
        return $this->handleEditProfile($request, $profile);
    }

    #[Route('/home', name: 'profile_home')]
    public function profileHome(Request $request, RecipeRepository $recipeRepository, CommentRepository $commentRepository): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $profile = $user->getProfile();

        // Si aucun profil n'existe, gérer la création du profil
        if (!$profile) {
            return $this->handleNewProfile($request, $user);
        }

        // Si le profil existe, afficher les recettes et commentaires
        $latestRecipes = $recipeRepository->findLatestRecipes(5); // Les 5 dernières recettes
        $latestComments = $commentRepository->findLatestCommentsByUser($user, 3); // Les 3 derniers commentaires

        return $this->render('profile/home.html.twig', [
            'latestRecipes' => $latestRecipes,
            'latestComments' => $latestComments,
            'profile' => $profile,
        ]);
    }

    private function handleNewProfile(Request $request, $user): Response
    {
        $profile = new Profile();
        $profile->setIdUser($user);
        $profile->setActive(true);

        $form = $this->createForm(ProfileType::class, $profile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->avatarService->handleAvatarUpload($profile);
            $this->entityManager->persist($profile);
            $this->entityManager->flush();

            return $this->redirectToRoute('profile_home');
        }

        // Chemin de l'avatar par défaut
        $defaultAvatar = 'images/avatars/default/default-avatar.png';

        return $this->render('profile/home.html.twig', [
            'form' => $form->createView(),
            'defaultAvatar' => $defaultAvatar,
            'profile' => null, // Pas de profil à afficher
        ]);
    }

    private function handleEditProfile(Request $request, Profile $profile): Response
    {
        $form = $this->createForm(ProfileType::class, $profile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $oldAvatarName = $profile->getAvatarName();
            $this->avatarService->handleAvatarUpload($profile);
            $this->entityManager->flush();

            if ($profile->getAvatarName() !== $oldAvatarName && $oldAvatarName !== null) {
                // Supprimer l'ancien avatar et ses versions
                $this->avatarService->removeOldAvatar($oldAvatarName, $profile);
            }

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/index.html.twig', [
            'profile' => $profile,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_profile_delete', methods: ['POST'])]
    public function deleteProfile(Request $request, TokenStorageInterface $tokenStorage): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $profile = $user->getProfile();

        $csrfToken = new CsrfToken('delete' . $profile->getId(), $request->request->get('_token'));
        if (!$this->csrfTokenManager->isTokenValid($csrfToken)) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        if ($profile) {
            // Vérifier si l'avatar est défini
            if ($profile->getAvatarName() !== null) {
                $this->avatarService->handleAvatarRemoval($profile);
            }
            $this->entityManager->remove($profile);
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        $tokenStorage->setToken(null);
        $request->getSession()->invalidate();

        $this->addFlash('success', 'Votre profil et compte utilisateur ont été supprimés avec succès.');

        return $this->redirectToRoute('app_home');
    }
}