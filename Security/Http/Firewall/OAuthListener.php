<?php

/**
 * (c) ForeverGlory <http://foreverglory.me/>
 * 
 * For the full copyright and license information, please view the LICENSE
 */

namespace Glory\Bundle\OAuthBundle\Security\Http\Firewall;

use Glory\Bundle\OAuthBundle\OAuth\OwnerMapAwareInterface;
use Glory\Bundle\OAuthBundle\OAuth\OwnerMapAwareTrait;
use HWI\Bundle\OAuthBundle\OAuth\ResourceOwnerInterface;
use HWI\Bundle\OAuthBundle\Security\Core\Authentication\Token\OAuthToken;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Firewall\AbstractAuthenticationListener;

/**
 * OAuthListener
 * 
 * @author ForeverGlory <foreverglory@qq.com>
 */
class OAuthListener extends AbstractAuthenticationListener implements OwnerMapAwareInterface
{

    use OwnerMapAwareTrait;

    /**
     * @var ResourceOwnerInterface 
     */
    protected $owner;

    /**
     * {@inheritDoc}
     */
    public function requiresAuthentication(Request $request)
    {
        $owners = $this->ownerMap->getOwners();
        //对 check_path 的路径进行比对
        foreach ($owners as $owner) {
            $service = $owner->getName();
            $check_path = $this->options['check_path'];
            if ('/' !== $check_path[0]) {
                $path = $this->contarner->get('router')->generateUri($check_path, ['service' => $service]);
            } else {
                $path = str_replace($check_path, '{service}', $service);
            }
            if ($this->httpUtils->checkRequestPath($request, $path)) {
                $this->owner = $owner;
                return true;
            }
        }
        return false;
    }

    /**
     * {@inheritDoc}
     */
    protected function attemptAuthentication(Request $request)
    {
        $this->handleOAuthError($request);

        /* @var ResourceOwnerInterface $owner */
        $owner = $this->owner;

        if (!$owner) {
            throw new AuthenticationException('No resource owner match the request.');
        }

        if (!$owner->handles($request)) {
            throw new AuthenticationException('No oauth code in the request.');
        }

        // If resource owner supports only one url authentication, call redirect
        if ($request->query->has('authenticated') && $owner->getOption('auth_with_one_url')) {
            $request->attributes->set('service', $owner->getName());

            return new RedirectResponse(sprintf('%s?code=%s&authenticated=true', $this->httpUtils->generateUri($request, 'glory_oauth_connect'), $request->query->get('code')));
        }

        $owner->isCsrfTokenValid($request->get('state'));

        $accessToken = $owner->getAccessToken(
                $request, $this->httpUtils->createRequest($request, $checkPath)->getUri()
        );

        $token = new OAuthToken($accessToken);
        $token->setResourceOwnerName($owner->getName());

        return $this->authenticationManager->authenticate($token);
    }

    /**
     * Detects errors returned by resource owners and transform them into
     * human readable messages
     *
     * @param Request $request
     *
     * @throws AuthenticationException
     */
    private function handleOAuthError(Request $request)
    {
        $error = null;

        // Try to parse content if error was not in request query
        if ($request->query->has('error') || $request->query->has('error_code')) {
            if ($request->query->has('error_description') || $request->query->has('error_message')) {
                throw new AuthenticationException(rawurldecode($request->query->get('error_description', $request->query->get('error_message'))));
            }

            $content = json_decode($request->getContent(), true);
            if (JSON_ERROR_NONE === json_last_error() && isset($content['error'])) {
                if (isset($content['error']['message'])) {
                    throw new AuthenticationException($content['error']['message']);
                }

                if (isset($content['error']['code'])) {
                    $error = $content['error']['code'];
                } elseif (isset($content['error']['error-code'])) {
                    $error = $content['error']['error-code'];
                } else {
                    $error = $request->query->get('error');
                }
            }
        } elseif ($request->query->has('oauth_problem')) {
            $error = $request->query->get('oauth_problem');
        }

        if (null !== $error) {
            throw new AuthenticationException($this->transformOAuthError($error));
        }
    }

    /**
     * Transforms OAuth error codes into human readable format
     *
     * @param string $errorCode
     *
     * @return string
     */
    private function transformOAuthError($errorCode)
    {
        // "translate" error to human readable format
        switch ($errorCode) {
            case 'access_denied':
                return 'You have refused access for this site.';

            case 'authorization_expired':
                return 'Authorization expired.';

            case 'bad_verification_code':
                return 'Bad verification code.';

            case 'consumer_key_rejected':
                return 'You have refused access for this site.';

            case 'incorrect_client_credentials':
                return 'Incorrect client credentials.';

            case 'invalid_assertion':
                return 'Invalid assertion.';

            case 'redirect_uri_mismatch':
                return 'Redirect URI mismatches configured one.';

            case 'unauthorized_client':
                return 'Unauthorized client.';

            case 'unknown_format':
                return 'Unknown format.';
        }

        return sprintf('Unknown OAuth error: "%s".', $errorCode);
    }

}