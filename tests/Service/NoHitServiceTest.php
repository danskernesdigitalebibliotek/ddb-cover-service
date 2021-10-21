<?php

/**
 * @file
 * Test cases for the NoHitsService service.
 */

namespace App\Tests\Service;

use App\Service\NoHitService;
use App\Utils\Types\IdentifierType;
use ItkDev\MetricsBundle\Service\MetricsService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class NoHitServiceTest.
 */
class NoHitServiceTest extends TestCase
{
    private EventDispatcherInterface $dispatcher;
    private MetricsService $metricsService;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->metricsService = $this->createMock(MetricsService::class);
    }

    /**
     * Test that service does nothing if logging not enabled.
     */
    public function testNoHitLoggingNotEnabled()
    {
        $noHitsService = $this->getNoHitsService(false);
        $requestIdentifiers = ['123456', '234567', '345678'];
        $foundIdentifiers = ['123456'];

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
        return new NoHitService($enabled, $this->dispatcher, $this->metricsService);
    }
}
