<?php

namespace App\EventSubscriber;

use App\Entity\Gallery;
use App\Service\GalleryService;
use Doctrine\ORM\Events as DoctrineEvents;
use Vich\UploaderBundle\Event\Event;
use Vich\UploaderBundle\Event\Events as VichEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class GalleryEventSubscriber implements EventSubscriberInterface
{
    private $galleryService;

    public function __construct(GalleryService $galleryService)
    {
        $this->galleryService = $galleryService;
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
        if ($entity instanceof Gallery) {
            $this->galleryService->handleImageUpload($entity);
        }
    }

    public function onPreRemove($args)
    {
        $entity = $args->getObject();
        if ($entity instanceof Gallery) {
            $this->galleryService->handleImageRemoval($entity);
        }
    }
}