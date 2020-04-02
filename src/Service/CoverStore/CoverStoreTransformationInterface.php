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
     * @param int $width
     *   The original image width for the url linked to
     * @param int $height
     *   The original image height for the url linked to
     * @param string $namedSize
     *   The named size to use
     *
     * @return string|null
     *   If the transformation has bigger dimensions than the original image null will be returned
     *
     * @throws CoverStoreTransformationException
     */
    public function transform(string $url, int $width, int $height, string $namedSize = 'default'): ?string;

    /**
     * Apply all configured transformations.
     *
     * @param string $url
     *   The base URL to transform
     * @param int $width
     *   The original image width for the url linked to
     * @param int $height
     *   The original image height for the url linked to
     *
     * @return array
     *   All transformations keyed by name (If the transformation has bigger dimensions than the original image null will
     *   be returned as the URL.)
     */
    public function transformAll(string $url, int $width, int $height): array;

    /**
     * Get metadata about an given format.
     *
     * @param string $format
     *   The format to get metadata about
     *
     * @return array
     *   Array with metadata about the format
     *
     * @throws CoverStoreTransformationException
     *   If format does not exists
     */
    public function getFormatMetadata(string $format): array;

    /**
     * Return the names of the formats available.
     *
     * @return string[]
     *   The format defined in configuration keyed by format name
     */
    public function getFormats(): array;
}
