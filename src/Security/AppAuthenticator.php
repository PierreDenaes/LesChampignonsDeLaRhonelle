<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class AppAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->get('email');

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($request->get('password')),
            [
                new CsrfTokenBadge('authenticate', $request->get('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Vérification de l'URL de redirection stockée dans la session ou dans la requête
        $targetPath = $request->get('_target_path') ?? $this->getTargetPath($request->getSession(), $firewallName);

        // Si une URL cible est trouvée, redirection prioritaire vers cette URL
        if ($targetPath) {
            return new RedirectResponse($targetPath);
        }

        $user = $token->getUser();
        $roles = $token->getUser()->getRoles();

        if (in_array('ROLE_ADMIN', $roles, true)) {
            return new RedirectResponse($this->urlGenerator->generate('admin'));
        } elseif (in_array('ROLE_USER', $roles, true)) {
            // Vérifie que le profil existe avant d'accéder à getFirstname()
            if ($user->getProfile()) {
                $request->getSession()->getFlashBag()->add('success', 'Bonjour ' . $user->getProfile()->getFirstname() . ', bienvenue sur votre profil !');
            } else {
                $request->getSession()->getFlashBag()->add('success', 'Pour commencer, merci de renseigner votre profil !');
            }
        
            return new RedirectResponse($this->urlGenerator->generate('app_profile'));
        } else {
            throw new \Exception('No route found for user role.');
        }
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}