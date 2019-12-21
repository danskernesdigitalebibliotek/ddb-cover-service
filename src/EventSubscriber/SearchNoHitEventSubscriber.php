<?php

/**
 * @file
 */

namespace App\EventSubscriber;

use App\Event\SearchNoHitEvent;
use App\Utils\Message\ProcessMessage;
use Enqueue\Client\ProducerInterface;
use Enqueue\Util\JSON;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class SearchNoHitEventSubscriber.
 */
class SearchNoHitEventSubscriber implements EventSubscriberInterface
{
    private $producer;
    private $enabled;

    /**
     * SearchNoHitEventSubscriber constructor.
     *
     * @param producerInterface $producer
     *   Queue producer to send messages (jobs)
     * @param ParameterBagInterface $params
     *   Access to environment variables
     */
    public function __construct(ProducerInterface $producer, ParameterBagInterface $params)
    {
        $this->producer = $producer;

        try {
            $this->enabled = $params->get('app.enable.no.hits');
        } catch (ParameterNotFoundException $exception) {
            $this->enabled = true;
        }
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
        if ($this->enabled) {
            foreach ($event->getNoHits() as $noHit) {
                $message = new ProcessMessage();
                $message->setIdentifierType($noHit->getIsType())
                    ->setIdentifier($noHit->getIsIdentifier());

                $this->producer->sendEvent('SearchNoHitsTopic', JSON::encode($message));
            }
        }
    }
}
