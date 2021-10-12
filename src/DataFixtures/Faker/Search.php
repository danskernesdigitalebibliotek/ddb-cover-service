<?php
/**
 * @file
 * Fake Search class for generating test data
 */

namespace App\DataFixtures\Faker;

/**
 * Class Search.
 *
 * @psalm-suppress MissingConstructor
 */
class Search
{
    private ?int $id;
    private ?string $isIdentifier;
    private ?string $isType;
    private ?string $imageUrl;
    private ?string $imageFormat;
    private ?int $width;
    private ?int $height;

    /**
     * Get the id.
     *
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set the id.
     *
     * @param int $id
     *
     * @return $this
     */
    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the international standard identifier.
     *
     * @return string|null
     */
    public function getIsIdentifier(): ?string
    {
        return $this->isIdentifier;
    }

    /**
     * Set the international standard identifier.
     *
     * @param string $isIdentifier
     *
     * @return $this
     */
    public function setIsIdentifier(string $isIdentifier): self
    {
        $this->isIdentifier = $isIdentifier;

        return $this;
    }

    /**
     * Get the type of international standard identifier.
     *
     * @return string|null
     */
    public function getIsType(): ?string
    {
        return $this->isType;
    }

    /**
     * Set the type of international standard identifier.
     *
     * @param string $isType
     *
     * @return $this
     */
    public function setIsType(string $isType): self
    {
        $this->isType = $isType;

        return $this;
    }

    /**
     * Get the image URL.
     *
     * @return string|null
     */
    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    /**
     * Set the image URL.
     *
     * @param string $imageUrl
     *
     * @return $this
     */
    public function setImageUrl(string $imageUrl): self
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

    /**
     * Get the image format.
     *
     * @return string|null
     */
    public function getImageFormat(): ?string
    {
        return $this->imageFormat;
    }

    /**
     * Set the image format.
     *
     * @param string $imageFormat
     *
     * @return $this
     */
    public function setImageFormat(string $imageFormat): self
    {
        $this->imageFormat = $imageFormat;

        return $this;
    }

    /**
     * Get the iamge width.
     *
     * @return int
     */
    public function getWidth(): ?int
    {
        return $this->width;
    }

    /**
     * Set the image width.
     *
     * @param int $width
     *
     * @return $this
     */
    public function setWidth(int $width): self
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Get the image height.
     *
     * @return int|null
     */
    public function getHeight(): ?int
    {
        return $this->height;
    }

    /**
     * Set the image heigth.
     *
     * @param int $height
     *
     * @return $this
     */
    public function setHeight(int $height): self
    {
        $this->height = $height;

        return $this;
    }
}
