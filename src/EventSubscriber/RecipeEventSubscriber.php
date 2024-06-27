<?php

namespace App\EventSubscriber;

use App\Entity\Recipe;
use App\Service\RecipeService;
use Doctrine\ORM\Events as DoctrineEvents;
use Vich\UploaderBundle\Event\Event;
use Vich\UploaderBundle\Event\Events as VichEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RecipeEventSubscriber implements EventSubscriberInterface
{
    private $recipeService;

    public function __construct(RecipeService $recipeService)
    {
        $this->recipeService = $recipeService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            VichEvents::POST_UPLOAD => 'onPostUpload',
            DoctrineEvents::preRemove => 'onPreRemove',
        ];
    }

    public function onPostUpload(Event $event)
    {
        $entity = $event->getObject();
        if ($entity instanceof Recipe) {
            $this->recipeService->handleImageUpload($entity);
        }
    }

    public function onPreRemove($args)
    {
        $entity = $args->getObject();
        if ($entity instanceof Recipe) {
            $this->recipeService->handleImageRemoval($entity);
        }
    }
}