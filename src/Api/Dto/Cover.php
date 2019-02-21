<?php

/**
 * @file
 * Cover Data Transfer Object (DTO). This DTO defines the /api/cover/{type}/{id} endpoint.
 *
 * However, because api-platform doesn't play nice with multiple {parameters} in path
 * individual DTOs are defined for all valid {type} values. These are necessary to enable
 * api-platform to generate IRIs for the cover resources.
 *
 * @see https://api-platform.com/docs/core/dto/
 */

namespace App\Api\Dto;

use App\Api\Filter\SearchFilter;
use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}},
 *     collectionOperations={
 *          "get"={
 *              "method"="GET",
 *              "path"="/cover/{type}",
 *          },
 *     },
 *     itemOperations={
 *          "get"={
 *              "method"="GET",
 *              "path"="/cover/{type}/{id}",
 *          }
 *     },
 * )
 *
 * @ApiFilter(SearchFilter::class, properties={"id": "exact", "format": "exact", "generic": "exact", "size": "exact"})
 */
class Cover implements IdentifierInterface
{
    /**
     * @ApiProperty(
     *     identifier=true,
     * )
     *
     * @Groups({"read"})
     */
    private $id;

    /**
     * @ApiProperty()
     *
     * @Groups({"read"})
     */
    private $type;

    /**
     * @ApiProperty()
     *
     * @Groups({"read"})
     */
    private $imageUrls;

    /**
     * Get the id.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Set the id.
     *
     * @param string $id
     *
     * @return IdentifierInterface
     */
    public function setId(string $id): IdentifierInterface
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Set type.
     *
     * @param string $type
     *
     * @return IdentifierInterface
     */
    public function setType(string $type): IdentifierInterface
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get array of image urls.
     *
     * @return array
     */
    public function getImageUrls(): array
    {
        return $this->imageUrls;
    }

    /**
     * Add an image url.
     *
     * @param ImageUrl $imageUrl
     *
     * @return IdentifierInterface
     */
    public function addImageUrl(ImageUrl $imageUrl): IdentifierInterface
    {
        $this->imageUrls[] = $imageUrl;

        return $this;
    }
}
