<?php

/**
 * @file
 * Test cases for the SearchNoHitEventSubscriber.
 */

namespace App\Tests\EventSubscriber;

use App\Event\SearchNoHitEvent;
use App\EventSubscriber\SearchNoHitEventSubscriber;
use App\Message\SearchNoHitsMessage;
use App\Utils\Types\IdentifierType;
use App\Utils\Types\NoHitItem;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Class SearchNoHitEventSubscriberTest.
 */
class SearchNoHitEventSubscriberTest extends TestCase
{
    private $bus;
    private $noHitsCache;

    /**
     * Set up test.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->bus = $this->createMock(MessageBusInterface::class);
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
        $this->bus->expects($this->never())->method('dispatch');

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
        $message1 = new SearchNoHitsMessage();
        $message1->setOperation(null)
            ->setIdentifierType('pid')
            ->setIdentifier('870970-basis:23452345')
            ->setVendorId(null)
            ->setImageId(null);
        $message2 = new SearchNoHitsMessage();
        $message2->setOperation(null)
            ->setIdentifierType('pid')
            ->setIdentifier('870970-basis:34563456')
            ->setVendorId(null)
            ->setImageId(null);
        $this->bus->expects($this->exactly(2))->method('dispatch')
            ->withConsecutive(
                [$this->equalTo($message1)],
                [$this->equalTo($message2)]
            )
            ->willReturn(new Envelope($message1));
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
        return new SearchNoHitEventSubscriber($noHitsProcessingEnabled, $this->bus, $this->noHitsCache);
    }
}
