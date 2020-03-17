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
            if (!is_null($url)) {
                return in_array($size, $imageSizes);
            }

            return false;
        }, ARRAY_FILTER_USE_BOTH);

        foreach ($urls as $size => $url) {
            $imageUrl = new ImageUrl();
            $imageUrl->setUrl($url);
            // @TODO Implement format
            $imageUrl->setFormat('jpeg');
            $imageUrl->setSize($size);

            $identifier->addImageUrl($imageUrl);
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
