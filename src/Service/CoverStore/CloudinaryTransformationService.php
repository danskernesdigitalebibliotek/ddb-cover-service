<?php

/**
 * @file
 * Implements transformation service for Cloudinary.
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
    private array $transformations;

    /**
     * CloudinaryTransformationService constructor.
     *
     * The transformations available are defined in the "cloudinary.yml" file that
     * can be found in the configuration folder.
     *
     * @param array $bindCloudinaryTransformations
     *   The transformation available from the configuration
     */
    public function __construct(array $bindCloudinaryTransformations)
    {
        $this->transformations = $bindCloudinaryTransformations;
    }

    /**
     * {@inheritdoc}
     */
    public function transform(string $url, int $width, int $height, string $namedSize = 'default'): ?string
    {
        // Find transformation if not available throw exception.
        if (!array_key_exists($namedSize, $this->transformations)) {
            throw new CoverStoreTransformationException('Unknown transformation: '.$namedSize);
        }
        $transformation = $this->transformations[$namedSize];

        // Insert named transformation if it exists.
        if (!empty($transformation['transformation'])) {
            // We do not allow up-scaling of images. Return null if the transformation will result in a larger image
            // than the original.
            if ($transformation['size'] > $height) {
                return null;
            }
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
    public function transformAll(string $url, int $width, int $height): array
    {
        $formats = array_keys($this->transformations);
        $transformedUrls = [];
        foreach ($formats as $format) {
            $transformedUrls[$format] = $this->transform($url, $width, $height, $format);
        }

        return $transformedUrls;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormatMetadata(string $format): array
    {
        if (!isset($this->transformations[$format])) {
            throw new CoverStoreTransformationException('Unknown transformation: '.$format);
        }

        return $this->transformations[$format];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormats(): array
    {
        return $this->transformations;
    }
}
