<?php

/**
 * @file
 * Contains the service for registering no hits.
 */

namespace App\Service;

use App\Event\SearchNoHitEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class NoHitService.
 */
class NoHitService
{
    protected $dispatcher;

    /**
     * NoHitService constructor.
     *
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Register no hits.
     *
     * @param array $noHits
     */
    public function registerNoHits(array $noHits)
    {
        $this->dispatcher->addListener(
            KernelEvents::TERMINATE,
            function (TerminateEvent $event) use ($noHits) {
                $noHitEvent = new SearchNoHitEvent($noHits);
                $this->dispatcher->dispatch($noHitEvent::NAME, $noHitEvent);
            }
        );
    }
}
