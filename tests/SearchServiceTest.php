<?php

/**
 * @file
 * Test cases for the Open Platform authentication service.
 */

namespace Tests;

use App\Exception\PlatformAuthException;
use App\Service\OpenPlatform\AuthenticationService;
use App\Service\OpenPlatform\SearchService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Class SearchServiceTest.
 */
class SearchServiceTest extends TestCase
{
    const TOKEN = 'fde1432d66d33e4cq66e5ad04757811e47864329';
    const IDENTIFIER = '9788770531214';

    /**
     * Test that an search reponse is parsed correctly.
     *
     * @throws PlatformAuthException
     * @throws \App\Exception\MaterialTypeException
     * @throws \App\Exception\PlatformSearchException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testSearch()
    {
        $body = '{"statusCode":200,"data":[{"title":["Tempelridderen"],"creator":["Jan Guillou"],"date":["2008"],"publisher":["Modtryk"],"pid":["870970-basis:27073301"],"identifierISBN":["9788770531214"]}],"hitCount":4,"more":false}';
        $service = $this->getAuthenticationService(false, $body);
        $material = $service->search($this::IDENTIFIER, 'isbn', 1);

        // Test basic information.
        $this->assertEquals('Tempelridderen', $material->getTitle());
        $this->assertEquals('Jan Guillou', $material->getCreator());
        $this->assertEquals('2008', $material->getDate());
        $this->assertEquals('Modtryk', $material->getPublisher());

        // Test that pid have been sat.
        $id = $material->getIdentifierByType('pid');
        $id = reset($id);
        $this->assertEquals('870970-basis:27073301', $id->getId());
        $this->assertEquals('pid', $id->getType());

        // Test that isbn have been sat.
        $id = $material->getIdentifierByType('isbn');
        $id = reset($id);
        $this->assertEquals('9788770531214', $id->getId());
        $this->assertEquals('isbn', $id->getType());
    }

    /**
     * Build mocks to ingject into the search service.
     *
     * @param bool $cacheHit
     *   Should we use the cache
     * @param string $body
     *   The response to get from http request
     *
     * @return searchService
     *   Mocked search service
     */
    private function getAuthenticationService(bool $cacheHit, string $body): SearchService
    {
        // Configure the parameters used in service constructor.
        $parameters = $this->createMock(ParameterBagInterface::class);
        $parameters->expects($this->atMost(3))
            ->method('get')
            ->withAnyParameters([
                'openPlatform.search.url',
                'openPlatform.search.index',
                'openPlatform.search.ttl',
            ])
            ->willReturn('https://search.local/', 'dkcclterm.is', 600);

        // Setup basic cache.
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->expects($this->any())
            ->method('get')
            ->willReturn('');

        if ($cacheHit) {
            $cacheItem->expects($this->once())
                ->method('isHit')
                ->willReturn($cacheHit);
        }

        $cache = $this->createMock(AdapterInterface::class);
        $cache->expects($this->once())
            ->method('getItem')
            ->willReturn($cacheItem);

        $logger = $this->createMock(LoggerInterface::class);

        $authentication = $this->createMock(AuthenticationService::class);
        $authentication->expects($this->any())
            ->method('getAccessToken')
            ->willReturn($this::TOKEN);

        return new SearchService($parameters, $cache, $logger, $authentication, $this->mockHttpClient($body));
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
