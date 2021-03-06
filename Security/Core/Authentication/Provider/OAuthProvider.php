<?php

/*
 * This file is part of the current project.
 * 
 * (c) ForeverGlory <http://foreverglory.me/>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glory\Bundle\OAuthBundle\Security\Core\Authentication\Provider;

use Glory\Bundle\OAuthBundle\Security\Core\Authentication\Token\OAuthToken;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Glory\Bundle\OAuthBundle\OAuth\Provider\OAuthProviderInterface;
use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthAwareUserProviderInterface;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Glory\Bundle\OAuthBundle\OAuth\OwnerMapAwareInterface;
use Glory\Bundle\OAuthBundle\OAuth\OwnerMapAwareTrait;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\HttpFoundation\RedirectResponse;

class OAuthProvider implements AuthenticationProviderInterface, OwnerMapAwareInterface
{

    use OwnerMapAwareTrait;

    /**
     * @var ContainerInterface 
     */
    protected $container;

    /**
     * @var UserCheckerInterface
     */
    protected $userChecker;

    /**
     * @param ContainerInterface $container ContainerInterface
     * @param UserCheckerInterface            $userChecker      User checker
     */
    public function __construct(ContainerInterface $container, UserCheckerInterface $userChecker)
    {
        $this->container = $container;
        $this->userChecker = $userChecker;
    }

    /**
     * {@inheritDoc}
     */
    public function supports(TokenInterface $token)
    {
        return $token instanceof OAuthToken && $this->ownerMap->hasOwner($token->getResourceOwnerName());
    }

    /**
     * {@inheritDoc}
     */
    public function authenticate(TokenInterface $token)
    {
        $ownerName = $token->getResourceOwnerName();
        $oauthUtil = $this->container->get('glory_oauth.util.token2oauth');
        $oauth = $oauthUtil->generate($token);

        $connect = $this->container->get('glory_oauth.connect');
        if (!$user = $connect->getConnect($oauth)) {
            if ($this->container->getParameter('glory_oauth.auto_register')) {
                $user = $connect->connect($oauth);
            } else {
                $key = time();
                $this->container->get('session')->set('glory_oauth.connect.oauth.' . $key, [$oauth->getOwner(), $oauth->getUsername()]);
                $url = $this->container->get('router')->generate('glory_oauth_register', ['key' => $key]);
                return new RedirectResponse($url);
            }
        }

        if (!$user instanceof UserInterface) {
            throw new BadCredentialsException('');
        }

        try {
            $this->userChecker->checkPreAuth($user);
            $this->userChecker->checkPostAuth($user);
        } catch (BadCredentialsException $e) {
            if ($this->hideUserNotFoundExceptions) {
                throw new BadCredentialsException('Bad credentials', 0, $e);
            }

            throw $e;
        }

        $token = new OAuthToken($token->getRawToken(), $user->getRoles());
        $token->setOwnerName($ownerName);
        $token->setUser($user);
        $token->setAuthenticated(true);

        return $token;
    }

}
