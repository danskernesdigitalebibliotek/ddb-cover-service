<?php

/**
 * @file
 * Test cases for the NoHitsService service.
 */

namespace App\Tests\Service;

use App\Service\MetricsService;
use App\Service\NoHitService;
use App\Utils\Types\IdentifierType;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class NoHitServiceTest.
 */
class NoHitServiceTest extends TestCase
{
    private $dispatcher;
    private $metricsService;
    private $noHitsCache;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->metricsService = $this->createMock(MetricsService::class);
        $this->noHitsCache = $this->createMock(CacheItemPoolInterface::class);
    }

    /**
     * Test that service does nothing if logging not enabled.
     */
    public function testNoHitLoggingNotEnabled()
    {
        $noHitsService = $this->getNoHitsService(false);
        $requestIdentifiers = ['123456', '234567', '345678'];
        $foundIdentifiers = ['123456'];

        $this->noHitsCache->expects($this->never())->method('getItems');
        $this->dispatcher->expects($this->never())->method('addListener');

        $noHitsService->handleSearchNoHits(IdentifierType::ISBN, $requestIdentifiers, $foundIdentifiers);
    }

    /**
     * Test no hit logging caching and event dispatch.
     */
    public function testNoHitLoggingEnabled()
    {
        $noHitsService = $this->getNoHitsService(true);
        $requestIdentifiers = ['870970-basis:12341234', '870970-basis:23452345', '870970-basis:34563456'];
        $foundIdentifiers = ['870970-basis:12341234'];

        $cachedItem = $this->createMock(CacheItemInterface::class);
        $cachedItem->method('isHit')->willReturn(true);
        $cachedItem->method('getKey')->willReturn('pid.870970_basis_23452345');

        $nonCachedItem = $this->createMock(CacheItemInterface::class);
        $nonCachedItem->method('isHit')->willReturn(false);
        $nonCachedItem->method('getKey')->willReturn('pid.870970_basis_34563456');
        $nonCachedItem->method('get')->willReturn('870970-basis:34563456');

        $this->noHitsCache->method('getItems')->willReturn([$cachedItem, $nonCachedItem]);

        // Assert that identifier get set for cached item
        $nonCachedItem->expects($this->once())->method('set')
            ->with('870970-basis:34563456');

        // Assert that item is save deferred and committed
        $this->noHitsCache->expects($this->once())->method('saveDeferred');
        $this->noHitsCache->expects($this->once())->method('commit');

        // Assert that Kernel terminate Event is used
        $this->dispatcher->expects($this->once())->method('addListener')
            ->with(KernelEvents::TERMINATE);

        $noHitsService->handleSearchNoHits(IdentifierType::PID, $requestIdentifiers, $foundIdentifiers);
    }

    /**
     * Get initialized NoHitService.
     *
     * @param bool $enabled
     *   Is no hit logging enabled
     *
     * @return NoHitService
     *   An initialized NoHitService
     */
    private function getNoHitsService(bool $enabled): NoHitService
    {
        return new NoHitService($enabled, $this->dispatcher, $this->metricsService, $this->noHitsCache);
    }
}
