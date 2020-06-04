<?php

namespace App\Tests;

use App\Security\TokenAuthenticator;
use App\Security\User;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Class TokenAuthenticatorTest.
 */
class TokenAuthenticatorTest extends TestCase
{
    private $httpClient;
    private $cache;
    private $item;
    private $userProvider;
    private $tokenAuthenticator;
    private $logger;

    /**
     * Setup mocks.
     */
    public function setUp()
    {
        parent::setUp();

        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->cache = $this->createMock(AdapterInterface::class);
        $this->item = $this->createMock(ItemInterface::class);
        $this->userProvider = $this->createMock(UserProviderInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->tokenAuthenticator = new TokenAuthenticator('id', 'secret', 'https://auth.test', $this->cache, $this->httpClient, $this->logger);
    }

    /**
     * Test that cached tokens does not trigger call to Open Platform.
     */
    public function testCachedTokensAreReturnedFromCache()
    {
        $this->cache->method('getItem')->willReturn($this->item);
        $this->cache->expects($this->once())->method('getItem')->with('12345678');

        $this->item->method('isHit')->willReturn(true);
        $this->item->method('get')->willReturn(new User());
        $this->item->expects($this->once())->method('get');

        $this->httpClient->expects($this->never())->method('request');

        $this->tokenAuthenticator->getUser('Bearer 12345678', $this->userProvider);
    }

    /**
     * Test that non active users are denied.
     */
    public function testTokenCallToOpenPlatform()
    {
        $this->cache->method('getItem')->willReturn($this->item);
        $this->item->method('isHit')->willReturn(false);

        $response = $this->createMock(ResponseInterface::class);

        $this->httpClient->method('request')->willReturn($response);
        $this->httpClient->expects($this->once())->method('request')->with('POST', 'https://auth.test?access_token=12345678', [
            'auth_basic' => ['id', 'secret'],
        ]);

        $this->tokenAuthenticator->getUser('Bearer 12345678', $this->userProvider);
    }

    /**
     * Test that access is denied if Open Platform does not return HTTP 200.
     */
    public function testAccessDeniedIfRequestNot200()
    {
        $this->cache->method('getItem')->willReturn($this->item);
        $this->item->method('isHit')->willReturn(false);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn('401');

        $this->httpClient->method('request')->willReturn($response);

        $user = $this->tokenAuthenticator->getUser('Bearer 12345678', $this->userProvider);
        $this->assertNull($user, 'TokenAuthenticator should return null (access denied) if it receives a non 200 response code');
    }

    /**
     * Test that access is denied if request throws exception.
     */
    public function testAccessDeniedIfRequestException()
    {
        $this->cache->method('getItem')->willReturn($this->item);
        $this->item->method('isHit')->willReturn(false);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn('401');

        $this->httpClient->method('request')->willThrowException(new HttpException(500));

        $this->expectException(HttpException::class);
        $user = $this->tokenAuthenticator->getUser('Bearer 12345678', $this->userProvider);
        $this->assertNull($user, 'TokenAuthenticator should return null (access denied) if request throws exception');
    }

    /**
     * Test that access denied if user non 'active' in Open Platform.
     *
     * @throws Exception
     */
    public function testNonActiveUserDenied()
    {
        $this->cache->method('getItem')->willReturn($this->item);
        $this->item->method('isHit')->willReturn(false);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn('200');
        $expires = new \DateTime('now + 2 days', new \DateTimeZone('UTC'));
        $json = '{
            "active": false,
            "clientId": "client-id-hash",
            "expires": "'.$expires->format('Y-m-d\TH:i:s.u\Z').'",
            "agency": "888777",
            "uniqueId": null,
            "search": {
                "profile": "abcd",
                "agency": "888777"
            },
            "type": "anonymous",
            "name": "DDB CMS",
            "contact": {
                "owner": {
                    "name": "Hans Hansen",
                    "email": "hans@hansen.dk",
                    "phone": "11 22 33 44"
                }
            }
        }';
        $response->method('getContent')->willReturn($json);

        $this->httpClient->method('request')->willReturn($response);

        $user = $this->tokenAuthenticator->getUser('Bearer 12345678', $this->userProvider);
        $this->assertNull($user, 'TokenAuthenticator should return null (access denied) if user not active');
    }

    /**
     * Test that access denied if token is expired.
     *
     * @throws Exception
     */
    public function testExpiredTokenIsDenied()
    {
        $this->cache->method('getItem')->willReturn($this->item);
        $this->item->method('isHit')->willReturn(false);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn('200');
        $expires = new \DateTime('now - 2 days', new \DateTimeZone('UTC'));
        $json = '{
            "active": true,
            "clientId": "client-id-hash",
            "expires": "'.$expires->format('Y-m-d\TH:i:s.u\Z').'",
            "agency": "888777",
            "uniqueId": null,
            "search": {
                "profile": "abcd",
                "agency": "888777"
            },
            "type": "anonymous",
            "name": "DDB CMS",
            "contact": {
                "owner": {
                    "name": "Hans Hansen",
                    "email": "hans@hansen.dk",
                    "phone": "11 22 33 44"
                }
            }
        }';
        $response->method('getContent')->willReturn($json);

        $this->httpClient->method('request')->willReturn($response);

        $user = $this->tokenAuthenticator->getUser('Bearer 12345678', $this->userProvider);
        $this->assertNull($user, 'TokenAuthenticator should return null (access denied) if token expired');
    }

    /**
     * Test that access denied if we receive an error from Open Platform.
     *
     * @throws Exception
     */
    public function testErrorTokenIsDenied()
    {
        $this->cache->method('getItem')->willReturn($this->item);
        $this->item->method('isHit')->willReturn(false);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn('200');
        $json = '{
            "error":"Invalid client and/or secret"
        }';
        $response->method('getContent')->willReturn($json);

        $this->httpClient->method('request')->willReturn($response);

        $user = $this->tokenAuthenticator->getUser('Bearer 12345678', $this->userProvider);
        $this->assertNull($user, 'TokenAuthenticator should return null (access denied) if error received from Open Platform');
    }

    /**
     * Test that access denied if we receive invalid json from Open Platform.
     *
     * @throws Exception
     */
    public function testInvalidJsonTokenIsDenied()
    {
        $this->cache->method('getItem')->willReturn($this->item);
        $this->item->method('isHit')->willReturn(false);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn('200');
        $expires = new \DateTime('now - 2 days', new \DateTimeZone('UTC'));
        $json = '{
            "active": true,error
            "clientId": "client-id-hash",
            "expires": "'.$expires->format('Y-m-d\TH:i:s.u\Z').'",
            "agency": "888777",
            "uniqueId": null,
            "search": {
                "profile": "abcd",
                "agency": "888777"
            },
            "type": "anonymous",
            "name": "DDB CMS",
            "contact": {
                "owner": {
                    "name": "Hans Hansen",
                    "email": "hans@hansen.dk",
                    "phone": "11 22 33 44"
                }
            }
        }';
        $response->method('getContent')->willReturn($json);

        $this->httpClient->method('request')->willReturn($response);

        $user = $this->tokenAuthenticator->getUser('Bearer 12345678', $this->userProvider);
        $this->assertNull($user, 'TokenAuthenticator should return null (access denied) if error received from Open Platform');
    }

    /**
     * Test that access granted if user is 'active' in Open Platform.
     *
     * @throws Exception
     */
    public function testActiveUSerAllowed()
    {
        $this->cache->method('getItem')->willReturn($this->item);
        $this->item->method('isHit')->willReturn(false);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn('200');
        $expires = new \DateTime('now + 2 days', new \DateTimeZone('UTC'));
        $json = '{
            "active": true,
            "clientId": "client-id-hash",
            "expires": "'.$expires->format('Y-m-d\TH:i:s.u\Z').'",
            "agency": "888777",
            "uniqueId": null,
            "search": {
                "profile": "abcd",
                "agency": "888777"
            },
            "type": "anonymous",
            "name": "DDB CMS",
            "contact": {
                "owner": {
                    "name": "Hans Hansen",
                    "email": "hans@hansen.dk",
                    "phone": "11 22 33 44"
                }
            }
        }';
        $response->method('getContent')->willReturn($json);

        $this->httpClient->method('request')->willReturn($response);

        $user = $this->tokenAuthenticator->getUser('Bearer 12345678', $this->userProvider);
        $this->assertInstanceOf(User::class, $user, 'TokenAuthenticator should return a "User" object for valid tokens');
        $this->assertEquals('888777', $user->getAgency());
        $this->assertEquals('client-id-hash', $user->getClientId());
        $this->assertEquals('888777', $user->getUsername());
        $this->assertEquals('12345678', $user->getPassword());
    }
}
