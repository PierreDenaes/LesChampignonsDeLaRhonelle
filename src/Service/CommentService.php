<?php

namespace App\Service;

use App\Entity\Recipe;
use App\Entity\Comment;
use App\Entity\Profile;
use App\Form\CommentType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use App\Event\CommentCreatedEvent;

class CommentService
{
    private EntityManagerInterface $entityManager;
    private FormFactoryInterface $formFactory;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        EntityManagerInterface $entityManager,
        FormFactoryInterface $formFactory,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->entityManager = $entityManager;
        $this->formFactory = $formFactory;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function handleCommentForm(
        Request $request,
        ?Profile $userProfile,
        Recipe $recipe
    ): ?FormInterface {
        $existingComment = $this->getExistingComment($userProfile, $recipe);

        if ($existingComment) {
            return null;  // L'utilisateur a déjà commenté
        }

        $comment = new Comment();
        $form = $this->formFactory->create(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $comment->setRecipe($recipe);
                $comment->setAuthor($userProfile);

                $this->entityManager->persist($comment);
                $this->entityManager->flush();

                // Dispatcher l'événement de création de commentaire
                $event = new CommentCreatedEvent($comment);
                $this->eventDispatcher->dispatch($event, CommentCreatedEvent::NAME);

                return null;  // Commentaire créé avec succès
            } else {
                // Gérer les erreurs de validation si nécessaire
            }
        }

        return $form;
    }

    public function getExistingComment(?Profile $userProfile, Recipe $recipe): ?Comment
    {
        if ($userProfile === null) {
            return null;
        }
        return $this->entityManager->getRepository(Comment::class)->findOneBy([
            'recipe' => $recipe,
            'author' => $userProfile,
        ]);
    }
}