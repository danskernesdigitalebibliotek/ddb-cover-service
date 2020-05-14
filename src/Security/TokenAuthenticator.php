<?php
/**
 * @file
 * Token authentication using 'adgangsplatform' introspection end-point.
 */

namespace App\Security;

use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Class TokenAuthenticator.
 */
class TokenAuthenticator extends AbstractGuardAuthenticator
{
    private $client;
    private $cache;

    private $clientId;
    private $clientSecret;
    private $endPoint;

    /**
     * TokenAuthenticator constructor.
     *
     * @param string $openPlatformId
     * @param string $openPlatformSecret
     * @param string $openPlatformUrl
     * @param AdapterInterface $tokenCache
     * @param HttpClientInterface $httpClient
     */
    public function __construct(string $openPlatformId, string $openPlatformSecret, string $openPlatformUrl, AdapterInterface $tokenCache, HttpClientInterface $httpClient)
    {
        $this->client = $httpClient;
        $this->cache = $tokenCache;

        $this->clientId = $openPlatformId;
        $this->clientSecret = $openPlatformSecret;
        $this->endPoint = $openPlatformUrl;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Request $request)
    {
        return $request->headers->has('authorization');
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials(Request $request)
    {
        return $request->headers->get('authorization');
    }

    /**
     * {@inheritdoc}
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        if (null === $credentials) {
            // The token header was empty, authentication fails with HTTP Status
            // Code 401 "Unauthorized"
            return null;
        }

        // Parse token information from the bearer authorization header.
        preg_match('/Bearer\s(\w+)/', $credentials, $matches);
        if (2 !== count($matches)) {
            return null;
        }

        $token = $matches[1];

        // Try getting item from cache.
        $item = $this->cache->getItem($token);
        if ($item->isHit()) {
            return $item->get();
        }

        try {
            $response = $this->client->request('POST', $this->endPoint.'?access_token='.$token, [
                'auth_basic' => [$this->clientId, $this->clientSecret],
            ]);

            if (200 !== $response->getStatusCode()) {
                return null;
            }

            $content = $response->getContent();
            $data = json_decode($content);

            // Token not valid, hence not active at the introspection end-point.
            if (false === $data->active) {
                return null;
            }
        } catch (HttpExceptionInterface $e) {
            return null;
        } catch (ExceptionInterface $e) {
            return null;
        }

        // Create user object.
        $tokenExpireDataTime = new \DateTime($data->expires, new \DateTimeZone('Europe/Copenhagen'));
        $user = new User();
        $user->setPassword($token);
        $user->setExpires($tokenExpireDataTime);
        $user->setAgency($data->agency);
        $user->setAuthType($data->type);
        $user->setClientId($data->clientId);

        // If the default expire for token cache (1 day) is shorter than the tokens reminding expire use the tokens
        // expire timestamp.
        if ($tokenExpireDataTime->getTimestamp() < time() - 86400) {
            $item->expiresAfter($tokenExpireDataTime->getTimestamp());
        }

        // Store access token in local cache.
        $item->set($user);
        $this->cache->save($item);

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        // In case of an token, no credential check is needed.
        // Return `true` to cause authentication success
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $data = [
            'message' => 'Authentication failed',
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * {@inheritdoc}
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $data = [
            'message' => 'Authentication Required',
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsRememberMe()
    {
        return false;
    }
}
