<?php

/**
 * @file
 * Contains the service for registering no hits.
 */

namespace App\Service;

use App\Event\SearchNoHitEvent;
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
    protected $dispatcher;

    /**
     * NoHitService constructor.
     *
     * @param ParameterBagInterface $params
     *   Access to environment variables
     * @param EventDispatcherInterface $dispatcher
     *   Event dispatcher
     */
    public function __construct(ParameterBagInterface $params, EventDispatcherInterface $dispatcher)
    {
        try {
            $this->noHitsProcessingEnabled = $params->get('app.enable.no.hits');
        } catch (ParameterNotFoundException $exception) {
            $this->noHitsProcessingEnabled = true;
        }

        $this->dispatcher = $dispatcher;
    }

    /**
     * Register no hits.
     *
     * @param array $noHits
     */
    public function registerNoHits(array $noHits)
    {
        if ($this->noHitsProcessingEnabled) {
            $this->dispatcher->addListener(
                KernelEvents::TERMINATE,
                function (TerminateEvent $event) use ($noHits) {
                    $noHitEvent = new SearchNoHitEvent($noHits);
                    $this->dispatcher->dispatch($noHitEvent::NAME, $noHitEvent);
                }
            );
        }
    }
}
