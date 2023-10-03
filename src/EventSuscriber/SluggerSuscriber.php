<?php

namespace App\EventSuscriber;

use App\Entity\Post;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\String\Slugger\SluggerInterface;

class SluggerSuscriber implements EventSubscriberInterface
{
//    private $slugger;

    public function __construct(private SluggerInterface $slugger, private Security $security)
    {
//        $this->slugger = $slugger;
    }
    public static function getSubscribedEvents()
    {
        return [
            BeforeEntityPersistedEvent::class => ["addSlug"],
            BeforeEntityUpdatedEvent::class => ["updateSlug"]
        ] ;
    }

    public function addSlug(BeforeEntityPersistedEvent $event){
        $entity = $event->getEntityInstance();
        $user = $this->security->getUser();
        if($entity instanceof Post){
            $slug = strtolower($this->slugger->slug($entity->getTitre()));
            $entity->setSlug($slug)
            ->setAuthor($user);
        }
        else
            return;
    }

    public function updateSlug(BeforeEntityUpdatedEvent $event){
        $entity = $event->getEntityInstance();
        if($entity instanceof Post){
            $slug = strtolower($this->slugger->slug($entity->getTitre()));
            $entity->setSlug($slug);
        }
        else
            return;
    }
}