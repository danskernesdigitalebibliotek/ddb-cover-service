<?php

/**
 * @file
 * ImageUrl Data Transfer Object (DTO).
 *
 * @see https://api-platform.com/docs/core/dto/
 */

namespace App\Api\Dto;

use ApiPlatform\Core\Annotation\ApiProperty;

/**
 * Class ImageUrl.
 */
final class ImageUrl
{
    /**
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *              "type"="string",
     *              "format"="url",
     *              "nullable"="true",
     *              "example"="https://res.cloudinary.com/dandigbib/image/upload/v1543609481/bogportalen.dk/9788702246841.jpg"
     *          }
     *     }
     * )
     */
    private $url;

    /**
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *              "type": "string",
     *              "example"="jpeg"
     *          }
     *     }
     * )
     */
    private $format;

    /**
     * @ApiProperty(
     *     attributes={
     *          "openapi_context"={
     *              "type" : "string",
     *              "enum": {"default", "original", "small", "medium", "large"},
     *              "example" : "large"
     *          }
     *     }
     * )
     */
    private $size;

    /**
     * Get url.
     *
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * Set url.
     *
     * @param string|null $url
     *
     * @return $this
     */
    public function setUrl(?string $url): self
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get format.
     *
     * @return string
     */
    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * Set format.
     *
     * @param string $format
     *
     * @return ImageUrl
     */
    public function setFormat(string $format): self
    {
        $this->format = $format;

        return $this;
    }

    /**
     * Get size.
     *
     * @return string
     */
    public function getSize(): string
    {
        return $this->size;
    }

    /**
     * Set size.
     *
     * @param string $size
     *
     * @return ImageUrl
     */
    public function setSize(string $size): self
    {
        $this->size = $size;

        return $this;
    }
}
