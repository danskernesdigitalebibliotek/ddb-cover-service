<?php

/**
 * @file
 * Isbn Data Transfer Object (DTO). This DTO defines the /api/cover/isbn/{id} resource.
 * Works in unison with the 'Cover' DTO.
 *
 * @see https://api-platform.com/docs/core/dto/
 */

namespace App\Api\Dto;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Utils\Types\IdentifierType;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}},
 *     itemOperations={
 *          "get"={
 *              "method"="GET",
 *              "path"="/cover/isbn/{id}",
 *          }
 *     },
 * )
 */
final class Isbn extends Cover
{
    /**
     * @ApiProperty(
     *     identifier=true,
     * )
     *
     * @Groups({"read"})
     */
    protected $id;

    /**
     * @ApiProperty()
     *
     * @Groups({"read"})
     */
    protected $type;

    /**
     * @ApiProperty()
     *
     * @Groups({"read"})
     */
    protected $imageUrls;

    /**
     * Isbn constructor.
     */
    public function __construct()
    {
        $this->setType(IdentifierType::ISBN);
    }
}
