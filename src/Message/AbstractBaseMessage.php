<?php

/**
 * @file
 */

namespace App\Message;

/**
 * Class BaseMessage.
 */
abstract class AbstractBaseMessage
{
    private string $operation;
    private string $identifierType;
    private string $identifier;
    private int $vendorId;
    private int $imageId;
    private bool $useSearchCache = true;

    /**
     * @return string
     */
    public function getOperation(): string
    {
        return $this->operation;
    }

    /**
     * @param string $operation
     *
     * @return AbstractBaseMessage
     */
    public function setOperation(string $operation): self
    {
        $this->operation = $operation;

        return $this;
    }

    /**
     * @return string
     */
    public function getIdentifierType(): string
    {
        return $this->identifierType;
    }

    /**
     * @param string $type
     *
     * @return AbstractBaseMessage
     */
    public function setIdentifierType(string $type): self
    {
        $this->identifierType = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     *
     * @return AbstractBaseMessage
     */
    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * @return int
     */
    public function getVendorId(): int
    {
        return $this->vendorId;
    }

    /**
     * @param int $vendorId
     *
     * @return AbstractBaseMessage
     */
    public function setVendorId(int $vendorId): self
    {
        $this->vendorId = $vendorId;

        return $this;
    }

    /**
     * @return int
     */
    public function getImageId(): int
    {
        return $this->imageId;
    }

    /**
     * @param int $imageId
     *
     * @return AbstractBaseMessage
     */
    public function setImageId(int $imageId): self
    {
        $this->imageId = $imageId;

        return $this;
    }

    /**
     * Use search cache.
     *
     * @return bool|null
     *   Defaults to true if not set
     */
    public function useSearchCache(): ?bool
    {
        return $this->useSearchCache;
    }

    /**
     * Should the search cache be used when processing the message.
     *
     * @param bool $useSearchCache
     *   True to use or false to by-pass search cache
     *
     * @return AbstractBaseMessage
     */
    public function setUseSearchCache(bool $useSearchCache): self
    {
        $this->useSearchCache = $useSearchCache;

        return $this;
    }
}
