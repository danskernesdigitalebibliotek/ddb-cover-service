<?php

/**
 * @file
 */

namespace App\EventSubscriber;

use App\Event\SearchNoHitEvent;
use App\Utils\Message\ProcessMessage;
use App\Utils\Types\NoHitItem;
use Enqueue\Client\ProducerInterface;
use Enqueue\Util\JSON;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class SearchNoHitEventSubscriber.
 */
class SearchNoHitEventSubscriber implements EventSubscriberInterface
{
    private $noHitsProcessingEnabled;
    private $producer;
    private $noHitsCache;

    /**
     * SearchNoHitEventSubscriber constructor.
     *
     * @param bool $bindEnableNoHits
     *   Is no hits processing enabled
     * @param producerInterface $producer
     *   Queue producer to send messages (jobs)
     * @param CacheItemPoolInterface $noHitsCache
     *   Cache pool for storing no hits
     */
    public function __construct(bool $bindEnableNoHits, ProducerInterface $producer, CacheItemPoolInterface $noHitsCache)
    {
        $this->noHitsProcessingEnabled = $bindEnableNoHits;

        $this->producer = $producer;
        $this->noHitsCache = $noHitsCache;
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
     *   Search no hit event
     */
    public function onSearchNoHitEvent(SearchNoHitEvent $event): void
    {
        if ($this->noHitsProcessingEnabled) {
            $keyedNoHits = [];

            /** @var NoHitItem $noHit */
            foreach ($event->getNoHits() as $noHit) {
                $cacheKey = $this->getValidCacheKey($noHit->getIsType(), $noHit->getIsIdentifier());
                $keyedNoHits[$cacheKey] = $noHit;
            }

            $cacheKeys = array_keys($keyedNoHits);
            $nonCommittedCacheItems = $this->getNonCachedNoHits($cacheKeys);
            $this->sendSearchNoHitEvents($nonCommittedCacheItems);
        }
    }

    /**
     * Send search no hit events.
     *
     * @param array $nonCommittedCacheItems
     *   Array of cache items
     */
    private function sendSearchNoHitEvents(array $nonCommittedCacheItems): void
    {
        foreach ($nonCommittedCacheItems as $cacheItem) {
            /** @var NoHitItem $noHitItem */
            $noHitItem = $cacheItem->get();
            $message = new ProcessMessage();
            $message->setIdentifierType($noHitItem->getIsType())
                ->setIdentifier($noHitItem->getIsIdentifier());

            $this->noHitsCache->saveDeferred($cacheItem);

            $this->producer->sendEvent('SearchNoHitsTopic', JSON::encode($message));
        }

        $this->noHitsCache->commit();
    }

    /**
     * Get cache items for the identifiers not present in the cache.
     *
     * @param array $keyedNoHits
     *   Array og cacheKey => NoHitItem pairs
     *
     * @return array
     *   Array of cache items not yet committed to cache
     */
    private function getNonCachedNoHits(array $keyedNoHits): array
    {
        $nonCommittedCacheItems = [];
        try {
            $keys = array_keys($keyedNoHits);
            $cacheItems = $this->noHitsCache->getItems($keys);
            foreach ($cacheItems as $cacheItem) {
                if (!$cacheItem->isHit()) {
                    /** @var NoHitItem $noHitItem */
                    $noHitItem = $keyedNoHits[$cacheItem->getKey()];
                    $cacheItem->set($noHitItem);
                    $nonCommittedCacheItems[] = $cacheItem;
                }
            }
        } catch (InvalidArgumentException $e) {
            // @TODO Logging?
        }

        return $nonCommittedCacheItems;
    }

    /**
     * Get an array of valid cache keys for the identifiers.
     *
     * Keys should only contain letters (A-Z, a-z), numbers (0-9) and the _ and . symbols.
     *
     * @see https://www.php-fig.org/psr/psr-6/
     * @see https://symfony.com/doc/current/components/cache/cache_items.html#cache-item-keys-and-values
     *
     * @param string $type
     *   The identifier type
     * @param string $identifier
     *   The identifier
     *
     * @return string
     *   The cache key
     */
    private function getValidCacheKey(string $type, string $identifier): string
    {
        if (IdentifierType::PID === $type) {
            $identifier = str_replace(':', '_', $identifier);
            $identifier = str_replace('-', '_', $identifier);
        }

        return $key = $type.'.'.$identifier;
    }
}
