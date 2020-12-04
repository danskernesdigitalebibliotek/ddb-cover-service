<?php

/**
 * @file
 */

namespace App\EventSubscriber;

use App\Event\SearchNoHitEvent;
use App\Message\SearchNoHitsMessage;
use App\Utils\Types\IdentifierType;
use App\Utils\Types\NoHitItem;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Class SearchNoHitEventSubscriber.
 */
class SearchNoHitEventSubscriber implements EventSubscriberInterface
{
    private $noHitsProcessingEnabled;
    private $bus;
    private $noHitsCache;
    private $requestId;

    /**
     * SearchNoHitEventSubscriber constructor.
     *
     * @param bool $bindEnableNoHits
     *   Is no hits processing enabled
     * @param string $bindRequestId
     *   The current requests unique ID
     * @param MessageBusInterface $bus
     *   Queue producer to send messages (jobs)
     * @param CacheItemPoolInterface $noHitsCache
     *   Cache pool for storing no hits
     */
    public function __construct(bool $bindEnableNoHits, string $bindRequestId, MessageBusInterface $bus, CacheItemPoolInterface $noHitsCache)
    {
        $this->noHitsProcessingEnabled = $bindEnableNoHits;
        $this->requestId = $bindRequestId;

        $this->bus = $bus;
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
     * Handle 'SearchNoHit' event.
     *
     * If a request for an unknown identifier is received we need to
     * perform additional indexing for that identifier to ensure we
     * don't have a cover for it. Given the expensive nature of the
     * indexing operations we cache weather a 'NoHit' has been generated
     * for this identifier within a specific time frame. This is
     * controlled by the lifetime config of the configured cache pool.
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
            $this->sendSearchNoHitMessage($nonCommittedCacheItems);
        }
    }

    /**
     * Send search no hit events.
     *
     * @param array $nonCommittedCacheItems
     *   Array of cache items
     */
    private function sendSearchNoHitMessage(array $nonCommittedCacheItems): void
    {
        foreach ($nonCommittedCacheItems as $cacheItem) {
            /** @var NoHitItem $noHitItem */
            $noHitItem = $cacheItem->get();
            $message = new SearchNoHitsMessage();
            $message->setIdentifierType($noHitItem->getIsType())
                ->setIdentifier($noHitItem->getIsIdentifier())
                ->setRequestId($this->requestId);

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
                if (!$cacheItem->isHit()) {
                    /** @var NoHitItem $noHitItem */
                    $cacheKey = $cacheItem->getKey();
                    $noHitItem = $keyedNoHits[$cacheKey];
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

        return $type.'.'.$identifier;
    }
}
