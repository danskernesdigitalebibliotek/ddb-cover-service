<?php

/**
 * @file
 * Test cases for the Open Platform authentication service.
 */

namespace Tests;

use App\Exception\PlatformAuthException;
use App\Service\OpenPlatform\AuthenticationService;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;

class AuthenticationServiceTest extends TestCase
{
    const TOKEN = 'fde1432d66d33e4cq66e5ad04757811e47864329';

    /**
     * Test that token is returned.
     *
     * @throws \App\Exception\PlatformAuthException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testGetAccessToken()
    {
        $body = '{"token_type": "bearer", "access_token": "'.$this::TOKEN.'", "expires_in": 2592000}';
        $service = $this->getAuthenticationService(false, $body);
        $this->assertEquals($this::TOKEN, $service->getAccessToken());
    }

    /**
     * Test that a token is return if cache is enabled.
     *
     * @throws \App\Exception\PlatformAuthException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testGetAccessTokenCache()
    {
        $service = $this->getAuthenticationService(true, '');
        $this->assertEquals($this::TOKEN, $service->getAccessToken());
    }

    /**
     * Test that PlatformAuthException is throw on client error.
     *
     * @throws PlatformAuthException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testErrorHandling()
    {
        $this->expectException(PlatformAuthException::class);
        $service = $this->getAuthenticationService(false, '');
        $service->getAccessToken();
    }

    /**
     * Build service with mocked injections.
     *
     * @param bool $cacheHit
     *   If FALSE don't hit cache
     * @param string $body
     *   The http request to reply with
     *
     * @return authenticationService
     *   The service to test
     */
    private function getAuthenticationService(bool $cacheHit, string $body): AuthenticationService
    {
        $parameters = $this->createMock(ParameterBagInterface::class);

        // Setup basic cache.
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->expects($this->any())
            ->method('get')
            ->willReturn($this::TOKEN);
        $cacheItem->expects($this->once())
            ->method('isHit')
            ->willReturn($cacheHit);

        $cache = $this->createMock(AdapterInterface::class);
        $cache->expects($this->once())
            ->method('getItem')
            ->willReturn($cacheItem);

        $logger = $this->createMock(LoggerInterface::class);

        return new AuthenticationService($parameters, $cache, $logger, $this->mockHttpClient($body));
    }

    /**
     * Mock guzzle http client.
     *
     * @param $body
     *   The response to the authentication request
     *
     * @return client
     *   Http mock client
     */
    private function mockHttpClient($body)
    {
        $mock = new MockHandler();

        if (empty($body)) {
            $mock->append(new RequestException('Error Communicating with Server', new Request('POST', '/')));
        } else {
            $mock->append(new Response(200, [], $body));
        }

        $handler = HandlerStack::create($mock);

        return new Client(['handler' => $handler]);
    }
}
