<?php

/**
 * @file
 */

namespace App\EventSubscriber;

use App\Event\SearchNoHitEvent;
use App\Message\SearchNoHitsMessage;
use App\Service\MetricsService;
use App\Utils\Types\IdentifierType;
use App\Utils\Types\NoHitItem;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Class SearchNoHitEventSubscriber.
 */
class SearchNoHitEventSubscriber implements EventSubscriberInterface
{
    private bool $noHitsProcessingEnabled;
    private MessageBusInterface $bus;
    private CacheItemPoolInterface $noHitsCache;
    private MetricsService $metricsService;

    /**
     * SearchNoHitEventSubscriber constructor.
     *
     * @param bool $bindEnableNoHits
     *   Is no hits processing enabled
     * @param MessageBusInterface $bus
     *   Queue producer to send messages (jobs)
     * @param CacheItemPoolInterface $noHitsCache
     *   Cache pool for storing no hits
     */
    public function __construct(bool $bindEnableNoHits, MessageBusInterface $bus, CacheItemPoolInterface $noHitsCache, MetricsService $metricsService)
    {
        $this->noHitsProcessingEnabled = $bindEnableNoHits;

        $this->bus = $bus;
        $this->noHitsCache = $noHitsCache;
        $this->metricsService = $metricsService;
    }

    /**
     * {@inheritdoc}
     *
     * Defines the events that we subscribe to.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            SearchNoHitEvent::NAME => 'onSearchNoHitEvent',
        ];
    }

    /**
     * Handle 'SearchNoHit' event.
     *
     * If a request for an unknown identifier is received we need to perform additional indexing for that identifier to
     * ensure we don't have a cover for it. Given the expensive nature of the indexing operations we cache weather a
     * 'NoHit' has been generated for this identifier within a specific time frame. This is controlled by the lifetime
     * config of the configured cache pool.
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

            $nonCommittedCacheItems = $this->getNonCachedNoHits($keyedNoHits);
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
            $message = new SearchNoHitsMessage();
            $message->setIdentifierType($noHitItem->getIsType())
                ->setIdentifier($noHitItem->getIsIdentifier());

            $this->noHitsCache->saveDeferred($cacheItem);

            $this->bus->dispatch($message);
        }

        $this->noHitsCache->commit();
    }

    /**
     * Get cache items for the identifiers not present in the cache.
     *
     * @param array $keyedNoHits
     *   Array of cacheKey => NoHitItem pairs
     *
     * @return array
     *   Array of cache items not yet committed to cache
     */
    private function getNonCachedNoHits(array $keyedNoHits): array
    {
        $nonCommittedCacheItems = [];
        try {
            $cacheKeys = array_keys($keyedNoHits);
            $cacheItems = $this->noHitsCache->getItems($cacheKeys);
            foreach ($cacheItems as $cacheItem) {
                /** @var CacheItem $cacheItem */
                if (!$cacheItem->isHit()) {
                    $this->metricsService->counter('no_hits_cache_miss', 'No hit not in cache', 1, ['type' => 'rest']);

                    /** @var NoHitItem $noHitItem */
                    $noHitItem = $keyedNoHits[$cacheItem->getKey()];
                    $cacheItem->set($noHitItem);
                    $nonCommittedCacheItems[] = $cacheItem;
                } else {
                    $this->metricsService->counter('no_hits_cache_hit', 'No hit found in cache', 1, ['type' => 'rest']);
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

        return $type.'.'.$identifier;
    }
}
