<?php

// src/Security/UserEnabledChecker.php
namespace App\Security;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

class UserEnabledChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof \App\Entity\User) {
            return;
        }

        if (!$user->isVerified()) {
            // Lancer une exception pour empêcher l'authentification
            throw new CustomUserMessageAuthenticationException('Votre adresse e-mail n\'est pas vérifiée. Veuillez vérifier votre e-mail pour vous connecter.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // Vous pouvez laisser cette méthode vide si vous n'avez pas de vérifications supplémentaires après l'authentification
    }
}