<?php

/**
 * @file
 * Contains the service for registering no hits.
 */

namespace App\Service;

use App\Event\SearchNoHitEvent;
use App\Utils\Types\NoHitItem;
use ItkDev\MetricsBundle\Service\MetricsService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class NoHitService.
 */
final class NoHitService
{
    /**
     * NoHitService constructor.
     *
     * @param EventDispatcherInterface $dispatcher
     *   Event dispatcher for search no hit events
     * @param MetricsService $metricsService
     *   Metrics service for performance logging
     * @param bool $noHitsProcessingEnabled
     *   Is no hits processing enabled
     */
    public function __construct(
        private readonly EventDispatcherInterface $dispatcher,
        private readonly MetricsService $metricsService,
        private readonly bool $noHitsProcessingEnabled
    ) {
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
        $notFoundIdentifiers = array_diff($requestIdentifiers, $foundIdentifiers);
        $this->dispatchNoHits($type, $notFoundIdentifiers);
    }

    /**
     * Dispatch no hits event.
     *
     * @param string $type
     *   The type ('pid', 'isbn', etc) of identifiers given
     * @param array $identifiers
     *   Array of no hit identifiers of {type}
     */
    private function dispatchNoHits(string $type, array $identifiers): void
    {
        if ($this->noHitsProcessingEnabled && !empty($identifiers)) {
            $noHits = [];

            $this->metricsService->gauge('no_hits_enabled', 'Total no-hits process enabled', 1, ['type' => 'rest']);
            $this->metricsService->counter('no_hits_total', 'Total number of no-hits', count($identifiers), ['type' => 'rest']);

            foreach ($identifiers as $identifier) {
                $noHits[] = new NoHitItem($type, $identifier);
            }

            $this->dispatcher->addListener(
                KernelEvents::TERMINATE,
                function (TerminateEvent $event) use ($noHits) {
                    $noHitEvent = new SearchNoHitEvent($noHits);
                    $this->dispatcher->dispatch($noHitEvent, SearchNoHitEvent::NAME);
                }
            );
        } else {
            $this->metricsService->gauge('no_hits_enabled', 'Total no-hits process enabled', 0, ['type' => 'rest']);
        }
    }
}
