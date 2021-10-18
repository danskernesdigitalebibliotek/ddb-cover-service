<?php

/**
 * @file
 * Contains the service for registering no hits.
 */

namespace App\Service;

use App\Event\SearchNoHitEvent;
use App\Utils\Types\NoHitItem;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class NoHitService.
 */
final class NoHitService
{
    private bool $noHitsProcessingEnabled;
    private EventDispatcherInterface $dispatcher;
    private MetricsService $metricsService;

    /**
     * NoHitService constructor.
     *
     * @param bool $bindEnableNoHits
     *   Is no hits processing enabled
     * @param EventDispatcherInterface $dispatcher
     *   Event dispatcher for search no hit events
     * @param MetricsService $metricsService
     *   Metrics service for performance logging
     */
    public function __construct(bool $bindEnableNoHits, EventDispatcherInterface $dispatcher, MetricsService $metricsService)
    {
        $this->noHitsProcessingEnabled = $bindEnableNoHits;

        $this->dispatcher = $dispatcher;
        $this->metricsService = $metricsService;
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

        $this->metricsService->counter('no_event_hits_total', 'Total number of no-hits', count($notFoundIdentifiers), ['type' => 'rest']);
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
        }
    }
}
