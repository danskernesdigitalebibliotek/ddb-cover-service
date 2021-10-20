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
     */
    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the international standard identifier.
     */
    public function getIsIdentifier(): ?string
    {
        return $this->isIdentifier;
    }

    /**
     * Set the international standard identifier.
     */
    public function setIsIdentifier(string $isIdentifier): self
    {
        $this->isIdentifier = $isIdentifier;

        return $this;
    }

    /**
     * Get the type of international standard identifier.
     */
    public function getIsType(): ?string
    {
        return $this->isType;
    }

    /**
     * Set the type of international standard identifier.
     */
    public function setIsType(string $isType): self
    {
        $this->isType = $isType;

        return $this;
    }

    /**
     * Get the image URL.
     */
    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    /**
     * Set the image URL.
     */
    public function setImageUrl(string $imageUrl): self
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

    /**
     * Get the image format.
     */
    public function getImageFormat(): ?string
    {
        return $this->imageFormat;
    }

    /**
     * Set the image format.
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
     */
    public function setWidth(int $width): self
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Get the image height.
     */
    public function getHeight(): ?int
    {
        return $this->height;
    }

    /**
     * Set the image heigth.
     */
    public function setHeight(int $height): self
    {
        $this->height = $height;

        return $this;
    }
}
