<?php

/**
 * @file
 * Pid Data Transfer Object (DTO). This DTO defines the /api/cover/pid/{id} resource.
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
 *              "path"="/cover/pid/{id}",
 *          }
 *     },
 * )
 */
final class Pid extends Cover
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
     * Pid constructor.
     */
    public function __construct()
    {
        $this->setType(IdentifierType::PID);
    }
}
