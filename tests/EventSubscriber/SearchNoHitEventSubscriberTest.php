<?php

/**
 * @file
 * Test cases for the SearchNoHitEventSubscriber.
 */

namespace App\Tests\EventSubscriber;

use App\Event\SearchNoHitEvent;
use App\EventSubscriber\SearchNoHitEventSubscriber;
use App\Utils\Types\IdentifierType;
use App\Utils\Types\NoHitItem;
use Enqueue\Client\ProducerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Class SearchNoHitEventSubscriberTest.
 */
class SearchNoHitEventSubscriberTest extends TestCase
{
    private $producer;
    private $noHitsCache;

    /**
     * Set up test.
     */
    public function setUp()
    {
        parent::setUp();

        $this->producer = $this->createMock(ProducerInterface::class);
        $this->noHitsCache = $this->createMock(CacheItemPoolInterface::class);
    }

    /**
     * Test that events are not sent if no hits processing is not enabled.
     */
    public function testNoHitsProcessingNotEnabled(): void
    {
        $noNitSubscriber = $this->getSearchNoHitEventSubscriber(false);
        $event = $this->getSearchNoHitEvent();

        $this->noHitsCache->expects($this->never())->method('getItems');
        $this->noHitsCache->expects($this->never())->method('commit');
        $this->producer->expects($this->never())->method('sendEvent');

        $noNitSubscriber->onSearchNoHitEvent($event);
    }

    /**
     * Test that events are sent correctly if no hits processing is enabled.
     */
    public function testNoHitsProcessingEnabled(): void
    {
        $noNitSubscriber = $this->getSearchNoHitEventSubscriber(true);
        $event = $this->getSearchNoHitEvent();

        // Setup mocks
        $cachedItem1 = $this->createMock(CacheItemInterface::class);
        $cachedItem1->method('isHit')->willReturn(true);
        $cachedItem1->method('getKey')->willReturn('pid.870970_basis_12345234');

        $cachedItem2 = $this->createMock(CacheItemInterface::class);
        $cachedItem2->method('isHit')->willReturn(false);
        $cachedItem2->method('getKey')->willReturn('pid.870970_basis_23452345');
        $noHitItem2 = new NoHitItem(IdentifierType::PID, '870970-basis:23452345');
        $cachedItem2->method('get')->willReturn($noHitItem2);

        $cachedItem3 = $this->createMock(CacheItemInterface::class);
        $cachedItem3->method('isHit')->willReturn(false);
        $cachedItem3->method('getKey')->willReturn('pid.870970_basis_34563456');
        $noHitItem3 = new NoHitItem(IdentifierType::PID, '870970-basis:34563456');
        $cachedItem3->method('get')->willReturn($noHitItem3);

        $cacheItems = [$cachedItem1, $cachedItem2, $cachedItem3];

        // Cache expects
        $this->noHitsCache->expects($this->once())->method('getItems')
            ->with(['pid.870970_basis_12345234', 'pid.870970_basis_23452345', 'pid.870970_basis_34563456'])
            ->willReturn($cacheItems);
        $cachedItem1->expects($this->never())->method('set');
        $cachedItem2->expects($this->once())->method('set')
            ->with($noHitItem2);
        $cachedItem3->expects($this->once())->method('set')
            ->with($noHitItem3);
        $this->noHitsCache->expects($this->exactly(2))->method('saveDeferred')
            ->withConsecutive(
                [$this->equalTo($cachedItem2)],
                [$this->equalTo($cachedItem3)]
            );
        $this->noHitsCache->expects($this->once())->method('commit');

        // Producer expects
        $json2 = '{"operation":null,"identifierType":"pid","identifier":"870970-basis:23452345","vendorId":null,"imageId":null}';
        $json3 = '{"operation":null,"identifierType":"pid","identifier":"870970-basis:34563456","vendorId":null,"imageId":null}';
        $this->producer->expects($this->exactly(2))->method('sendEvent')
            ->withConsecutive(
                [$this->equalTo('SearchNoHitsTopic'), $this->equalTo($json2)],
                [$this->equalTo('SearchNoHitsTopic'), $this->equalTo($json3)]
            );

        $noNitSubscriber->onSearchNoHitEvent($event);
    }

    /**
     * Get an example SearchNoHitEvent.
     *
     * @return SearchNoHitEvent
     *   A SearchNoHitEvent with three no hits
     */
    private function getSearchNoHitEvent(): SearchNoHitEvent
    {
        $noHits = [];
        $noHits[] = new NoHitItem(IdentifierType::PID, '870970-basis:12345234');
        $noHits[] = new NoHitItem(IdentifierType::PID, '870970-basis:23452345');
        $noHits[] = new NoHitItem(IdentifierType::PID, '870970-basis:34563456');

        return new SearchNoHitEvent($noHits);
    }

    /**
     * Get a SearchNoHitEventSubscriber instance.
     *
     * @param bool $noHitsProcessingEnabled
     *   Should no hits processing be enabled
     *
     * @return SearchNoHitEventSubscriber
     *   A configured SearchNoHitEventSubscriber
     */
    private function getSearchNoHitEventSubscriber(bool $noHitsProcessingEnabled): SearchNoHitEventSubscriber
    {
        $bag = $this->createMock(ParameterBagInterface::class);
        $bag->method('get')->with('app.enable.no.hits')
            ->willReturn($noHitsProcessingEnabled);

        return new SearchNoHitEventSubscriber($bag, $this->producer, $this->noHitsCache);
    }
}