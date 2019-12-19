<?php

/**
 * @file
 * Contains ResultEventSubscriber.
 */

namespace App\Service\VendorService\TheMovieDatabase\EventSubscriber;

use App\Service\VendorService\TheMovieDatabase\Event\ResultEvent;
use App\Service\VendorService\TheMovieDatabase\Message\ApiSearchMessage;
use Enqueue\Client\ProducerInterface;
use Enqueue\Util\JSON;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ResultEventSubscriber.
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
     * @param resultEvent $event
     *   The event to handle
     */
    public function onDataWellSearchEvent(ResultEvent $event)
    {
        $results = $event->getResults();

        foreach ($results as $pid => $meta) {
            $message = new ApiSearchMessage($event->getVendorId(), $event->getIdentifierType(), $pid, $meta);

            $this->producer->sendEvent('ApiSearchTopic', JSON::encode($message));
        }
    }
}
