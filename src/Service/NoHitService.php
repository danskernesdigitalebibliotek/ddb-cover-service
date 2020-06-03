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
    private $dispatcher;
    private $metricsService;
    private $noHitsProcessingEnabled;

    /**
     * NoHitService constructor.
     *
     * @param EventDispatcherInterface $dispatcher
     * @param MetricsService $metricsService
     * @param bool $envEnableNoHits
     */
    public function __construct(EventDispatcherInterface $dispatcher, MetricsService $metricsService, bool $envEnableNoHits)
    {
        $this->dispatcher = $dispatcher;
        $this->metricsService = $metricsService;
        $this->noHitsProcessingEnabled = $envEnableNoHits;
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

        $this->metricsService->counter('no_hit_event_duration_seconds', 'Total number of no-hits', count($notFoundIdentifiers), ['type' => 'rest']);
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
