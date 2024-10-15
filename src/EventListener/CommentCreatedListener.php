<?php

namespace App\EventListener;

use App\Event\CommentCreatedEvent;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class CommentCreatedListener
{
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function onCommentCreated(CommentCreatedEvent $event): void
    {
        $comment = $event->getComment();
        $recipe = $comment->getRecipe();
        $author = $recipe->getProfile();

        if (!$author || !$author->getIdUser()->getEmail()) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from(new Address('admin@leschampignonsdelarhonelle.com', 'Les Champignons de La Rhonelle'))
            ->to(new Address($author->getIdUser()->getEmail(), $author->getFirstname()))
            ->subject('Nouveau commentaire sur votre recette')
            ->htmlTemplate('emails/new_comment_notification.html.twig')
            ->context([
                'recipe' => $recipe,
                'author' => $author,
                'comment' => $comment,
            ]);

        $this->mailer->send($email);
    }
}