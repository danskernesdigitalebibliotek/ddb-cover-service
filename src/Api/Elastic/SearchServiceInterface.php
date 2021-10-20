<?php
/**
 * @file
 * Search service interface
 */

namespace App\Api\Elastic;

/**
 * Interface SearchServiceInterface.
 */
interface SearchServiceInterface
{
    /**
     * Search for covers.
     *
     * @param string $type        The type of identifiers
     * @param array  $identifiers The identifiers to search for
     */
    public function search(string $type, array $identifiers): array;
}
