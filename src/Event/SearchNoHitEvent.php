<?php

/**
 * @file
 */

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class SearchNoHitEvent.
 *
 * No hit event thrown when a search has no results.
 */
class SearchNoHitEvent extends Event
{
    public const NAME = 'app.search.nohit';

    private array $noHits;

    /**
     * SearchNoHitEvent constructor.
     *
     * @param array $noHits Array containing 'NoHitItem' objects
     */
    public function __construct(array $noHits)
    {
        $this->noHits = $noHits;
    }

    /**
     * Get the no hit type => identifiers.
     *
     * @return array The no hits array
     */
    public function getNoHits(): array
    {
        return $this->noHits;
    }
}
