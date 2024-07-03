<?php

namespace App\EventSubscriber;

use App\Entity\Profile;
use App\Service\AvatarService;
use Doctrine\ORM\Events as DoctrineEvents;
use Vich\UploaderBundle\Event\Event;
use Vich\UploaderBundle\Event\Events as VichEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AvatarEventSubscriber implements EventSubscriberInterface
{
    private $avatarService;

    public function __construct(AvatarService $avatarService)
    {
        $this->avatarService = $avatarService;
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
        if ($entity instanceof Profile) {
            $this->avatarService->handleAvatarUpload($entity);
        }
    }

    public function onPreRemove($args)
    {
        $entity = $args->getObject();
        if ($entity instanceof Profile) {
            $this->avatarService->handleAvatarRemoval($entity);
        }
    }
}