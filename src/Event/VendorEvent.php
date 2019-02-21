<?php

/**
 * @file
 */

namespace App\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class VendorInsertedEvent.
 *
 * Vendor event thrown when a new material is inserted into the database.
 */
class VendorEvent extends Event
{
    const NAME = 'app.vendor';

    private $type;
    private $identifiers;
    private $identifierType;
    private $vendorId;

    /**
     * VendorEvent constructor.
     *
     * @param string $type
     *   The type of event. See VendorState class.
     * @param array $identifiers
     *   The identifiers to process
     * @param string $identifierType
     *   The type of identifiers
     * @param string $vendorId
     *   The id of the vendor that triggered the event
     */
    public function __construct(string $type, array $identifiers, string $identifierType, string $vendorId)
    {
        $this->type = $type;
        $this->identifiers = $identifiers;
        $this->identifierType = $identifierType;
        $this->vendorId = $vendorId;
    }

    /**
     * Get the identifier.
     *
     * @return array
     *   The identifier
     */
    public function getIdentifiers(): array
    {
        return $this->identifiers;
    }

    /**
     * The type of the identifier.
     *
     * @return string
     */
    public function getIdentifierType(): string
    {
        return $this->identifierType;
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
    public function getType(): string
    {
        return $this->type;
    }
}
