<?php

/**
 * @file
 * Implements transformation service for cloudinary.
 */

namespace App\Service\CoverStore;

use App\Exception\CoverStoreTransformationException;

/**
 * Class CloudinaryTransformationService.
 *
 * Note: The transformations parameters has to be set to "allowed" at the
 *       Cloudinary web-page to make transformations available and the URL's
 *       generated here accessible.
 */
class CloudinaryTransformationService implements CoverStoreTransformationInterface
{
    private $transformations;

    /**
     * CloudinaryTransformationService constructor.
     *
     * The transformations available are defined in the "cloudinary.yml" file that
     * can be found in the configuration folder.
     *
     * @param array $cloudinaryTransformations
     *   The transformation available from the configuration
     */
    public function __construct(array $cloudinaryTransformations)
    {
        $this->transformations = $cloudinaryTransformations;
    }

    /**
     * {@inheritdoc}
     */
    public function transform(string $url, string $format = 'default'): string
    {
        // Find transformation if not available throw exception.
        if (!array_key_exists($format, $this->transformations)) {
            throw new CoverStoreTransformationException('Unknown transformation: '.$format);
        }
        $transformation = $this->transformations[$format];

        // Insert named transformation if it exists.
        if (!empty($transformation['transformation'])) {
            $url = str_replace('/image/upload/', '/image/upload/'.$transformation['transformation'].'/', $url);
        }

        // If extension conversion exists apply it.
        if (!empty($transformation['extension'])) {
            $parts = explode('.', $url);
            array_pop($parts);
            $parts[] = $transformation['extension'];
            $url = implode('.', $parts);
        }

        return $url;
    }

    /**
     * {@inheritdoc}
     */
    public function transformAll(string $url): array
    {
        $formats = array_keys($this->transformations);
        $transformedUrls = [];
        foreach ($formats as $format) {
            $transformedUrls[$format] = $this->transform($url, $format);
        }

        return $transformedUrls;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormats(): array
    {
        return $this->transformations;
    }
}
