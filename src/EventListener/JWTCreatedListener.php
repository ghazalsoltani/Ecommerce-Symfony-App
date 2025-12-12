<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Symfony\Component\Security\Core\User\UserInterface;

class JWTCreatedListener
{
    public function onJWTCreated(JWTCreatedEvent $event): void
    {
        /** @var \App\Entity\User $user */
        $user = $event->getUser();

        // Get current payload
        $payload = $event->getData();

        // Add custom data to the token
        $payload['id'] = $user->getId();
        $payload['firstname'] = $user->getFirstname();
        $payload['lastname'] = $user->getLastname();

        // Update the token payload
        $event->setData($payload);
    }
}