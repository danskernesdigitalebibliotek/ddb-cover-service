<?php

/**
 * @file
 * Contains the service for registering no hits.
 */

namespace App\Service;

use App\Event\SearchNoHitEvent;
use App\Utils\Types\NoHitItem;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class NoHitService.
 */
class NoHitService
{
    private $noHitsProcessingEnabled;
    private $dispatcher;
    private $noHitsCache;

    /**
     * NoHitService constructor.
     *
     * @param ParameterBagInterface $params
     * @param EventDispatcherInterface $dispatcher
     * @param AdapterInterface $noHitsCache
     */
    public function __construct(ParameterBagInterface $params, EventDispatcherInterface $dispatcher, AdapterInterface $noHitsCache)
    {
        try {
            $this->noHitsProcessingEnabled = $params->get('app.enable.no.hits');
        } catch (ParameterNotFoundException $exception) {
            $this->noHitsProcessingEnabled = true;
        }

        $this->dispatcher = $dispatcher;
        $this->noHitsCache = $noHitsCache;
    }

    /**
     * Register no hits.
     *
     * @param array $searchNoHitsItems
     *   Array of App\Utils\Types\NoHitItem
     */
    public function registerNoHits(array $searchNoHitsItems)
    {
        if ($this->noHitsProcessingEnabled) {
            $keyedSearchNoHitsItems = $this->getKeyedSearchNoHitArray($searchNoHitsItems);

            $nonCachedSearchNoHits = $this->getNonCachedSearchNoHits($keyedSearchNoHitsItems);
            $this->dispatchNoHits($keyedSearchNoHitsItems, ...$nonCachedSearchNoHits);
        }
    }

    /**
     * Get array identifiers key by unique cache key.
     *
     * @param array $searchNoHitsItems
     *   Array of App\Utils\Types\NoHitItem
     *
     * @return array
     *   An array og cacheKey => App\Utils\Types\NoHitItem pairs
     */
    private function getKeyedSearchNoHitArray(array $searchNoHitsItems): array
    {
        $result = [];
        foreach ($searchNoHitsItems as $searchNoHitsItem) {
            /**
             * @var NoHitItem $searchNoHitsItem
             */
            $key = $this->getValidCacheKey($searchNoHitsItem->getIsType(), $searchNoHitsItem->getIsIdentifier());
            $result[$key] = $searchNoHitsItem;
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
     * @param array $keyedSearchNoHitsItems
     *   Array og cacheKey => App\Utils\Types\NoHitItem pairs
     *
     * @return array
     *   Array of cache items with no hits in the cache
     */
    private function getNonCachedSearchNoHits(array $keyedSearchNoHitsItems): array
    {
        $nonCachedIdentifierItems = [];
        try {
            $keys = array_keys($keyedSearchNoHitsItems);
            $cacheItems = $this->noHitsCache->getItems($keys);
            foreach ($cacheItems as $cacheItem) {
                if (!$cacheItem->isHit()) {
                    /**
                     * @var CacheItemInterface $cacheItem
                     * @var NoHitItem $searchNoHitItem
                     */
                    $searchNoHitItem = $keyedSearchNoHitsItems[$cacheItem->getKey()];
                    $identifier = $searchNoHitItem->getIsIdentifier();
                    $cacheItem->set($identifier);
                    $nonCachedIdentifierItems[] = $cacheItem;
                }
            }
        } catch (InvalidArgumentException $e) {
            // @TODO Logging?
        }

        return $nonCachedIdentifierItems;
    }

    /**
     * Dispatch no hits event for no hits not found in the no hit cache.
     *
     * @param array $keyedSearchNoHitsItems
     *   Array og cacheKey => App\Utils\Types\NoHitItem pairs
     * @param CacheItemInterface ...$nonCachedSearchNoHits
     *   List of non cached cache items to dispatch search no hit events for
     */
    private function dispatchNoHits(array $keyedSearchNoHitsItems, CacheItemInterface ...$nonCachedSearchNoHits): void
    {
        if (!empty($nonCachedSearchNoHits)) {
            $noHits = [];

            foreach ($nonCachedSearchNoHits as $identifierItem) {
                $noHitItem = $keyedSearchNoHitsItems[$identifierItem->getKey()];
                $noHits[] = $noHitItem;

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
