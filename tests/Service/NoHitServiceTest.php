<?php

/**
 * @file
 * Test cases for the NoHitsService service.
 */

namespace App\Tests\Service;

use App\Service\NoHitService;
use App\Utils\Types\IdentifierType;
use App\Utils\Types\NoHitItem;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class NoHitServiceTest.
 */
class NoHitServiceTest extends TestCase
{
    private $dispatcher;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
    }

    /**
     * Test that service does nothing if logging not enabled.
     */
    public function testNoHitLoggingNotEnabled()
    {
        $noHitsService = $this->getNoHitsService(false);
        $noHits = $this->getSearchNoHits();

        $this->dispatcher->expects($this->never())->method('addListener');

        $noHitsService->registerNoHits($noHits);
    }

    /**
     * Test no hit logging caching and event dispatch.
     */
    public function testNoHitLoggingEnabled()
    {
        $noHitsService = $this->getNoHitsService(true);
        $noHits = $this->getSearchNoHits();

        // Assert that Kernel terminate Event is used
        $this->dispatcher->expects($this->once())->method('addListener')
            ->with(KernelEvents::TERMINATE);

        $noHitsService->registerNoHits($noHits);
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
        $bag = $this->createMock(ParameterBagInterface::class);
        $bag->method('get')->with('app.enable.no.hits')
            ->willReturn($enabled);

        return new NoHitService($bag, $this->dispatcher);
    }

    /**
     * Get an example SearchNoHitEvent.
     *
     * @return array
     *   Array with three no hits
     */
    private function getSearchNoHits(): array
    {
        $noHits = [];
        $noHits[] = new NoHitItem(IdentifierType::PID, '870970-basis:12345234');
        $noHits[] = new NoHitItem(IdentifierType::PID, '870970-basis:23452345');
        $noHits[] = new NoHitItem(IdentifierType::PID, '870970-basis:34563456');

        return $noHits;
    }
}
