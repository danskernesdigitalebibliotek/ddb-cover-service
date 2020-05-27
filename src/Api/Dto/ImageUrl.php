<?php

/**
 * @file
 * ImageUrl Data Transfer Object (DTO).
 *
 * @see https://api-platform.com/docs/core/dto/
 */

namespace App\Api\Dto;

/**
 * Class ImageUrl.
 */
final class ImageUrl
{
    private $url;
    private $format;
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
