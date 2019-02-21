<?php

/**
 * @file
 */

namespace App\EventSubscriber;

use App\Event\SearchNoHitEvent;
use App\Utils\Message\ProcessMessage;
use Enqueue\Client\ProducerInterface;
use Enqueue\Util\JSON;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class SearchNoHitEventSubscriber.
 */
class SearchNoHitEventSubscriber implements EventSubscriberInterface
{
    private $producer;

    /**
     * SearchNoHitEventSubscriber constructor.
     *
     * @param producerInterface $producer
     *   Queue producer to send messages (jobs)
     */
    public function __construct(ProducerInterface $producer)
    {
        $this->producer = $producer;
    }

    /**
     * {@inheritdoc}
     *
     * Defines the events that we subscribes to.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            SearchNoHitEvent::NAME => 'onSearchNoHitEvent',
        ];
    }

    /**
     * Event handler.
     *
     * @param SearchNoHitEvent $event
     */
    public function onSearchNoHitEvent(SearchNoHitEvent $event): void
    {
        foreach ($event->getNoHits() as $noHit) {
            $message = new ProcessMessage();
            $message->setIdentifierType($noHit->getIsType())
                ->setIdentifier($noHit->getIsIdentifier());

            $this->producer->sendEvent('SearchNoHitsTopic', JSON::encode($message));
        }
    }
}
