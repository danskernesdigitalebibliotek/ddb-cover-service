<?php
/**
 * @file
 * Wrapper for search no hit.
 */

namespace App\Utils\Types;

/**
 * Class NoHitItem.
 */
class NoHitItem
{
    private string $isType;
    private string $isIdentifier;

    /**
     * NoHitItem constructor.
     *
     * @param string $isType
     * @param string $isIdentifier
     */
    public function __construct(string $isType, string $isIdentifier)
    {
        $this->isType = $isType;
        $this->isIdentifier = $isIdentifier;
    }

    /**
     * @return string
     */
    public function getIsType(): string
    {
        return $this->isType;
    }

    /**
     * @return string
     */
    public function getIsIdentifier(): string
    {
        return $this->isIdentifier;
    }
}
