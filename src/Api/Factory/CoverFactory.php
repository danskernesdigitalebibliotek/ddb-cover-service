<?php

/**
 * @file
 * Identifier DTO factory.
 *
 * @see https://api-platform.com/docs/core/dto/
 */

namespace App\Api\Factory;

use App\Api\Dto\Cover;
use App\Api\Dto\ImageUrl;
use App\Service\CoverStore\CoverStoreTransformationInterface;

class CoverFactory
{
    private $transformer;

    /**
     * IdentifierFactory constructor.
     *
     * @param CoverStoreTransformationInterface $transformer
     *   The cover store transformation service to get urls for transformed covers
     */
    public function __construct(CoverStoreTransformationInterface $transformer)
    {
        $this->transformer = $transformer;
    }

    /**
     * Create Identifier Dto from identifier type.
     *
     * @param string $type
     *   The identifier type (e.g. 'pid', 'isbn', etc)
     * @param array $imageSizes
     *   The image sizes requested
     * @param array $data
     *   An array of key => values to set on the relevant properties
     *
     * @return Cover
     *   A new {type} identifier data transfer object (DTO) with values set from {data}
     */
    public function createCoverDto(string $type, array $imageSizes, array $data): Cover
    {
        $cover = new Cover();
        $this->setData($cover, $imageSizes, $data);

        return $cover;
    }

    /**
     * Set cover data.
     *
     * @param Cover $cover
     *   The identifier type (e.g. 'pid', 'isbn', etc).
     * @param array $imageSizes
     *   The image sizes requested
     * @param array $data
     *   An array of key => values to set on the relevant properties
     */
    private function setData(Cover $cover, array $imageSizes, array $data): void
    {
        $cover->setId($data['isIdentifier']);
        $cover->setType($data['isType']);

        $urls = $this->transformer->transformAll($data['imageUrl'], $data['width'], $data['height']);
        $urls = array_filter($urls, function (?string $url, string $size) use ($imageSizes) {
            return in_array($size, $imageSizes, true);
        }, ARRAY_FILTER_USE_BOTH);

        foreach ($urls as $size => $url) {
            $imageUrl = new ImageUrl();
            $imageUrl->setUrl($url);
            $imageUrl->setFormat($this->getFormat($size, $data['imageFormat']));
            $imageUrl->setSize($size);

            $cover->addImageUrl($imageUrl);
        }
    }

    /**
     * Get the image format for a given image size.
     *
     * @param string $imageSize
     *   The image sizes requested
     * @param string $originalFormat
     *   The format of the original image, e.g 'jpeg', 'png'
     *
     * @return string
     */
    private function getFormat(string $imageSize, string $originalFormat): string
    {
        $formats = $this->transformer->getFormats();

        if (!array_key_exists($imageSize, $formats)) {
            throw new \RuntimeException('Unknown image size: '.$imageSize);
        }

        $extension = $formats[$imageSize]['extension'] ?? null;
        $format = $formats[$imageSize]['extension'] ? $this->getFormatFromExt($extension) : $originalFormat;

        return $this->getFormatFromExt($format);
    }

    /**
     * Get the image format from a file extension.
     *
     * @param string $extension
     *   The file extension of the image file
     *
     * @return string
     *   The format of the image, e.g 'jpeg', 'png'
     */
    private function getFormatFromExt(string $extension): string
    {
        $extension = strtolower($extension);

        switch ($extension) {
            case 'jpg':
                return 'jpeg';
            default:
                return $extension;
        }
    }
}
