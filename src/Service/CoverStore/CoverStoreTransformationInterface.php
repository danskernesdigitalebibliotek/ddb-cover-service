<?php

/**
 * @file
 * Defines the functionality need by the frontend API to create the different
 * cover urls need.
 */

namespace App\Service\CoverStore;

use App\Exception\CoverStoreTransformationException;

/**
 * Interface CoverStoreTransformationInterface.
 */
interface CoverStoreTransformationInterface
{
    /**
     * Transform a base URL into an URL with applied transformations.
     *
     * @param string $url
     *   The base URL to transform
     * @param string $format
     *   The format to use
     *
     * @return string
     *
     * @throws CoverStoreTransformationException
     */
    public function transform(string $url, string $format = 'default'): string;

    /**
     * Apply all configured transformations.
     *
     * @param string $url
     *   The base URL to transform
     *
     * @return array
     *   All transformation keyed by name
     */
    public function transformAll(string $url): array;

    /**
     * Return the names of the formats available.
     *
     * @return string[]
     *   The format defined in configuration keyed by format name
     */
    public function getFormats(): array;
}
