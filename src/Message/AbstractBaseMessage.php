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
    private ?int $imageId = null;
    private bool $useSearchCache = true;
    private ?string $traceId = null;

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
     * @return $this
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
     * @return static
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
     * @return static
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
     * @return static
     */
    public function setVendorId(int $vendorId): self
    {
        $this->vendorId = $vendorId;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getImageId(): ?int
    {
        return $this->imageId;
    }

    /**
     * @param int|null $imageId
     *
     * @return static
     */
    public function setImageId(?int $imageId): self
    {
        $this->imageId = $imageId;

        return $this;
    }

    /**
     * Use search cache.
     *
     * @return bool
     *   Defaults to true if not set
     */
    public function useSearchCache(): bool
    {
        return $this->useSearchCache;
    }

    /**
     * Should the search cache be used when processing the message.
     *
     * @param bool $useIt
     *   True to use or false to by-pass search cache
     *
     * @return static
     */
    public function setUseSearchCache(bool $useIt): self
    {
        $this->useSearchCache = $useIt;

        return $this;
    }

    /**
     * Get request id (which is unique for the whole request).
     *
     * @return string|null
     *   The request id
     */
    public function getTraceId(): ?string
    {
        return $this->traceId;
    }

    /**
     * Set trace id (which is unique for the whole request).
     *
     * @param string $traceId
     *   The trace id used to trace this message between services
     *
     * @return AbstractBaseMessage
     */
    public function setTraceId(string $traceId): self
    {
        $this->traceId = $traceId;

        return $this;
    }
}
