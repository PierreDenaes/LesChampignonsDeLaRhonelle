<?php

namespace App\Controller;

use App\Entity\Profile;
use App\Form\ProfileType;
use App\Service\AvatarService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
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

    public function __construct(EntityManagerInterface $entityManager, Security $security, AvatarService $avatarService)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
        $this->avatarService = $avatarService;
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
            $profile = new Profile();
            $profile->setIdUser($user);
            $profile->setActive(true);
            $form = $this->createForm(ProfileType::class, $profile);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $this->avatarService->handleAvatarUpload($profile);
                $this->entityManager->persist($profile);
                $this->entityManager->flush();

                return $this->redirectToRoute('app_profile');
            }

            return $this->render('profile/new.html.twig', [
                'form' => $form->createView(),
            ]);
        }

        return $this->render('profile/index.html.twig', [
            'profile' => $profile,
        ]);
    }

    #[Route('/{id}', name: 'app_profile_show', methods: ['GET'])]
    public function show(Profile $profile): Response
    {
        return $this->render('profile/show.html.twig', [
            'profile' => $profile,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_profile_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Profile $profile): Response
    {
        $form = $this->createForm(ProfileType::class, $profile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->avatarService->handleAvatarUpload($profile);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_profile', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('profile/edit.html.twig', [
            'profile' => $profile,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_profile_delete', methods: ['POST'])]
    public function deleteProfile(Request $request, TokenStorageInterface $tokenStorage, EntityManagerInterface $entityManager)
    {
        $user = $this->getUser();

        $profile = $user->getProfile();

        if ($profile) {
            $this->avatarService->handleAvatarRemoval($profile);
            $entityManager->remove($profile);
        }

        $entityManager->remove($user);
        $entityManager->flush();

        $tokenStorage->setToken(null);
        $request->getSession()->invalidate();

        return $this->redirectToRoute('app_home');
    }
}