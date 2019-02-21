<?php

/**
 * @file
 */

namespace App\Utils\Message;

/**
 * Class ProcessMessage.
 */
class ProcessMessage implements \JsonSerializable
{
    private $operation;
    private $identifierType;
    private $identifier;
    private $vendorId;
    private $imageId;

    /**
     * {@inheritdoc}
     *
     * Serialization function for the object.
     */
    public function jsonSerialize()
    {
        $arr = [];
        foreach ($this as $key => $value) {
            $arr[$key] = $value;
        }

        return $arr;
    }

    /**
     * @return mixed
     */
    public function getOperation()
    {
        return $this->operation;
    }

    /**
     * @param mixed $operation
     *
     * @return ProcessMessage
     */
    public function setOperation($operation): self
    {
        $this->operation = $operation;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getIdentifierType()
    {
        return $this->identifierType;
    }

    /**
     * @param mixed $type
     *
     * @return ProcessMessage
     */
    public function setIdentifierType($type): self
    {
        $this->identifierType = $type;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param mixed $identifier
     *
     * @return ProcessMessage
     */
    public function setIdentifier($identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getVendorId()
    {
        return $this->vendorId;
    }

    /**
     * @param mixed $vendorId
     *
     * @return ProcessMessage
     */
    public function setVendorId($vendorId): self
    {
        $this->vendorId = $vendorId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getImageId()
    {
        return $this->imageId;
    }

    /**
     * @param mixed $imageId
     *
     * @return ProcessMessage
     */
    public function setImageId($imageId): self
    {
        $this->imageId = $imageId;

        return $this;
    }
}
