<?php

/**
 * @file
 * Identifier DTO factory.
 *
 * @see https://api-platform.com/docs/core/dto/
 */

namespace App\Api\Factory;

use App\Api\Dto\Faust;
use App\Api\Dto\IdentifierInterface;
use App\Api\Dto\ImageUrl;
use App\Api\Dto\Isbn;
use App\Api\Dto\Issn;
use App\Api\Dto\Pid;
use App\Service\CoverStore\CoverStoreTransformationInterface;
use App\Utils\Types\IdentifierType;

class IdentifierFactory
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
     * @return IdentifierInterface
     *   A new {type} identifier data transfer object (DTO) with values set from {data}
     */
    public function createIdentifierDto(string $type, array $imageSizes, array $data): IdentifierInterface
    {
        $identifier = $this->createDto($type);
        $this->setIdentifierData($identifier, $imageSizes, $data);

        return $identifier;
    }

    /**
     * Set identifier data.
     *
     * @param IdentifierInterface $identifier
     *   The identifier type (e.g. 'pid', 'isbn', etc).
     * @param array $imageSizes
     *   The image sizes requested
     * @param array $data
     *   An array of key => values to set on the relevant properties
     */
    private function setIdentifierData(IdentifierInterface $identifier, array $imageSizes, array $data): void
    {
        $identifier->setId($data['isIdentifier']);

        $urls = $this->transformer->transformAll($data['imageUrl'], $data['width'], $data['height']);
        $urls = array_filter($urls, function (?string $url, string $size) use ($imageSizes) {
            return in_array($size, $imageSizes, true);
        }, ARRAY_FILTER_USE_BOTH);

        foreach ($urls as $size => $url) {
            $imageUrl = new ImageUrl();
            $imageUrl->setUrl($url);
            // @TODO Implement format
            $imageUrl->setFormat($this->getFormat($size, $data['imageFormat']));
            $imageUrl->setSize($size);

            $identifier->addImageUrl($imageUrl);
        }
    }

    /**
     * Get the image format for a given image size.
     *
     * @param string $imageSize
     * @param string $originalFormat
     *
     * @return string
     */
    private function getFormat(string $imageSize, string $originalFormat): string
    {
        $formats = $this->transformer->getFormats();

        if (!array_key_exists($imageSize, $formats)) {
            throw new \RuntimeException('Unknown image size: '.$imageSize);
        }

        $extension = $formats[$imageSize] ?? $formats[$imageSize]['extension'];
        $format = $extension ? $this->getFormatFromExt($extension) : $originalFormat;

        return $this->getFormatFromExt($format);
    }

    /**
     * Get the image format from a file extension.
     *
     * @param string $extension
     *
     * @return string
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

    /**
     * Create new blank Identifier object from identifier type.
     *
     * @param string $type
     *   The identifier type (e.g. 'pid', 'isbn', etc).
     *
     * @return identifierInterface
     *   A new blank {type} identifier data transfer object (DTO)
     *
     * @throws UnknownIdentifierTypeException
     *   If identifier is not defined in App\Utils\Types\IdentifierType
     */
    private function createDto(string $type): IdentifierInterface
    {
        switch ($type) {
            case IdentifierType::ISBN:
                return new Isbn();
            case IdentifierType::ISSN:
                return new Issn();
            case IdentifierType::PID:
                return new Pid();
            case IdentifierType::FAUST:
                return new Faust();
            default:
                throw new UnknownIdentifierTypeException($type.' is an unknown identifier type');
        }
    }
}
