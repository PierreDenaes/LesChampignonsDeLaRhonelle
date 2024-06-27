<?php

namespace App\EventSubscriber;

use App\Entity\Profile;
use App\Service\AvatarService;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class AvatarEventSubscriber implements EventSubscriber
{
    private $avatarService;

    public function __construct(AvatarService $avatarService)
    {
        $this->avatarService = $avatarService;
    }

    public function getSubscribedEvents()
    {
        return [
            Events::preRemove,
        ];
    }

    public function preRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        if (!$entity instanceof Profile) {
            return;
        }

        if ($entity->getAvatarName() !== Profile::DEFAULT_AVATAR) {
            $this->avatarService->handleAvatarRemoval($entity);
        }
    }
}