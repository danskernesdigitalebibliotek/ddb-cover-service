<?php

/**
 * @file
 */

namespace App\Service\VendorService\TheMovieDatabase\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class VendorInsertedEvent.
 *
 * Vendor event thrown when a new material is inserted into the database.
 */
class ResultEvent extends Event
{
    public const NAME = 'app.the_movie_database.result';

    private $results;
    private $identifierType;
    private $vendorId;

    /**
     * VendorEvent constructor.
     *
     * @param string $type
     *   The type of event. See VendorState class.
     * @param array $results
     *   The identifiers to process
     * @param string $identifierType
     *   The type of identifiers
     * @param string $vendorId
     *   The id of the vendor that triggered the event
     */
    public function __construct(array $results, string $identifierType, string $vendorId)
    {
        $this->results = $results;
        $this->identifierType = $identifierType;
        $this->vendorId = $vendorId;
    }

    /**
     * Get the identifier.
     *
     * @return array
     *   The identifier
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * The vendors is of the provides the event is about.
     *
     * @return string
     *   The database id of the vendor
     */
    public function getVendorId(): string
    {
        return $this->vendorId;
    }

    /**
     * @return string
     */
    public function getIdentifierType(): string
    {
        return $this->identifierType;
    }
}
