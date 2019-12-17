<?php

/**
 * @file
 */

namespace App\Service\VendorService\TheMovieDatabase\EventSubscriber;

use App\Service\VendorService\TheMovieDatabase\Event\ResultEvent;
use App\Service\VendorService\TheMovieDatabase\Message\ApiSearchMessage;
use Enqueue\Client\ProducerInterface;
use Enqueue\Util\JSON;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class VendorEventSubscriber.
 */
class ResultEventSubscriber implements EventSubscriberInterface
{
    private $producer;

    /**
     * VendorEventSubscriber constructor.
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
    public static function getSubscribedEvents()
    {
        return [
            ResultEvent::NAME => 'onDataWellSearchEvent',
        ];
    }

    /**
     * Event handler.
     *
     * @param ResultEvent $event
     */
    public function onDataWellSearchEvent(ResultEvent $event)
    {
        $results = $event->getResults();

        foreach ($results as $pid => $meta) {
            $message = new ApiSearchMessage($event->getVendorId(), $event->getIdentifierType(), $pid, $meta['title'], $meta['date']);

            $this->producer->sendEvent('ApiSearchTopic', JSON::encode($message));
        }
    }
}
