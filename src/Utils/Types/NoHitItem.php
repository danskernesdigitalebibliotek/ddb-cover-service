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
     */
    public function __construct(string $isType, string $isIdentifier)
    {
        $this->isType = $isType;
        $this->isIdentifier = $isIdentifier;
    }

    public function getIsType(): string
    {
        return $this->isType;
    }

    public function getIsIdentifier(): string
    {
        return $this->isIdentifier;
    }
}
