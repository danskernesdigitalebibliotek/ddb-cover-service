<?php
/**
 * @file
 * Token authentication using 'adgangsplatform' introspection end-point.
 */

namespace App\Security;

use Psr\Log\LoggerInterface;
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
    private $logger;

    private $clientId;
    private $clientSecret;
    private $endPoint;

    /**
     * TokenAuthenticator constructor.
     *
     * @param string $bindOpenPlatformId
     * @param string $bindOpenPlatformSecret
     * @param string $bindOpenPlatformIntrospectionUrl
     * @param AdapterInterface $tokenCache
     * @param HttpClientInterface $httpClient
     * @param LoggerInterface $logger
     */
    public function __construct(string $bindOpenPlatformId, string $bindOpenPlatformSecret, string $bindOpenPlatformIntrospectionUrl, AdapterInterface $tokenCache, HttpClientInterface $httpClient, LoggerInterface $logger)
    {
        $this->client = $httpClient;
        $this->cache = $tokenCache;
        $this->logger = $logger;

        $this->clientId = $bindOpenPlatformId;
        $this->clientSecret = $bindOpenPlatformSecret;
        $this->endPoint = $bindOpenPlatformIntrospectionUrl;
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
                $this->logger->error(self::class.' http call to Open Platform returned status: '.$response->getStatusCode());

                return null;
            }

            $content = $response->getContent();
            $data = json_decode($content, false, 512, JSON_THROW_ON_ERROR);

            // Error from Open Platform
            if (isset($data->error)) {
                $this->logger->error(self::class.' token call to Open Platform returned error: '.$data->error);

                return null;
            }

            // Unknown format/token type
            if (isset($data->type) && 'anonymous' !== $data->type) {
                $this->logger->error(self::class.' token call to Open Platform returned unknown type: '.$data->type);

                return null;
            }

            // Token not active at the introspection end-point.
            if (isset($data->active) && false === $data->active) {
                return null;
            }

            // Token expired
            $tokenExpireDataTime = new \DateTime($data->expires, new \DateTimeZone('Europe/Copenhagen'));
            $now = new \DateTime();
            if ($now > $tokenExpireDataTime) {
                return null;
            }
        } catch (HttpExceptionInterface $e) {
            $this->logger->error(self::class.' http exception: '.$e->getMessage());

            return null;
        } catch (ExceptionInterface $e) {
            $this->logger->error(self::class.' exception: '.$e->getMessage());

            return null;
        } catch (\JsonException $e) {
            $this->logger->error(self::class.' json decode exception: '.$e->getMessage());

            return null;
        }

        // Create user object.
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
