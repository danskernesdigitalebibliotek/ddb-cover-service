<?php

/**
 * @file
 * Contains the service for registering no hits.
 */

namespace App\Service;

use App\Event\SearchNoHitEvent;
use App\Utils\Types\IdentifierType;
use App\Utils\Types\NoHitItem;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class NoHitService.
 */
final class NoHitService
{
    private $noHitsProcessingEnabled;
    private $dispatcher;
    private $metricsService;
    private $noHitsCache;

    /**
     * NoHitService constructor.
     *
     * @param bool $bindEnableNoHits
     * @param EventDispatcherInterface $dispatcher
     * @param MetricsService $metricsService
     * @param CacheItemPoolInterface $noHitsCache
     */
    public function __construct(bool $bindEnableNoHits, EventDispatcherInterface $dispatcher, MetricsService $metricsService, CacheItemPoolInterface $noHitsCache)
    {
        $this->noHitsProcessingEnabled = $bindEnableNoHits;

        $this->dispatcher = $dispatcher;
        $this->metricsService = $metricsService;
        $this->noHitsCache = $noHitsCache;
    }

    /**
     * Send event to register identifiers that gave no search results.
     *
     * @param string $type
     *   The type ('pid', 'isbn', etc) of identifiers given
     * @param array $requestIdentifiers
     *   Array of requested identifiers of {type}
     * @param array $foundIdentifiers
     *   Array of identifiers of {type} with covers found
     */
    public function handleSearchNoHits(string $type, array $requestIdentifiers, array $foundIdentifiers): void
    {
        if ($this->noHitsProcessingEnabled) {
            $notFoundIdentifiers = array_diff($requestIdentifiers, $foundIdentifiers);
            $notFoundKeyIdentifierArray = $this->getKeyIdentifierArray($type, $notFoundIdentifiers);

            $nonCachedIdentifierItems = $this->getNonCachedNoHits($notFoundKeyIdentifierArray);
            $this->dispatchNoHits($type, ...$nonCachedIdentifierItems);

            $this->metricsService->counter('no_hit_event_duration_seconds', 'Total number of no-hits', count($notFoundIdentifiers), ['type' => 'rest']);
        }
    }

    /**
     * Get array identifiers key by unique cache key.
     *
     * @param string $type
     *   The type ('pid', 'isbn', etc) of identifiers given
     * @param array $identifiers
     *   Array of identifiers of {type}
     *
     * @return array
     *   An array og cacheKey => value pairs
     */
    private function getKeyIdentifierArray(string $type, array $identifiers): array
    {
        $result = [];
        foreach ($identifiers as $identifier) {
            $key = $this->getValidCacheKey($type, $identifier);
            $result[$key] = $identifier;
        }

        return $result;
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

    /**
     * Get cache items for the identifiers not present in the cache.
     *
     * @param array $keyIdentifierArray
     *   Array og cacheKey => value pairs
     *
     * @return array
     *   Array of cache items with no hits in the cache
     */
    private function getNonCachedNoHits(array $keyIdentifierArray): array
    {
        $nonCachedIdentifierItems = [];
        try {
            $keys = array_keys($keyIdentifierArray);
            $items = $this->noHitsCache->getItems($keys);
            foreach ($items as $item) {
                if (!$item->isHit()) {
                    $item->set($keyIdentifierArray[$item->getKey()]);
                    $nonCachedIdentifierItems[] = $item;
                }
            }
        } catch (InvalidArgumentException $e) {
            // @TODO Logging?
        }

        return $nonCachedIdentifierItems;
    }

    /**
     * Dispatch no hits event.
     *
     * @param string $type
     *   The type ('pid', 'isbn', etc) of identifiers given
     * @param CacheItemInterface ...$identifierItems
     *   A list of identifier cache items
     */
    private function dispatchNoHits(string $type, CacheItemInterface ...$identifierItems): void
    {
        if (!empty($identifierItems)) {
            $noHits = [];

            foreach ($identifierItems as $identifierItem) {
                $noHits[] = new NoHitItem($type, $identifierItem->getKey());

                $this->noHitsCache->saveDeferred($identifierItem);
            }

            $this->noHitsCache->commit();

            $this->dispatcher->addListener(
                KernelEvents::TERMINATE,
                function (TerminateEvent $event) use ($noHits) {
                    $noHitEvent = new SearchNoHitEvent($noHits);
                    $this->dispatcher->dispatch($noHitEvent, SearchNoHitEvent::NAME);
                }
            );
        }
    }
}
