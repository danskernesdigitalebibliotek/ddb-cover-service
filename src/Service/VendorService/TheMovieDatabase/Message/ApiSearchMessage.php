<?php

namespace App\Service\VendorService\TheMovieDatabase\Message;

use App\Service\MoreInfoService\Types\IdentifierType;

class ApiSearchMessage implements \JsonSerializable
{
    private $vendorId;
    private $identifierType;
    private $pid;
    private $title;
    private $year;

    /**
     * ApiSearchMessage constructor.
     *
     * @param int|null $vendorId
     * @param string|null $identifierType
     * @param null $pid
     * @param string|null $title
     * @param string|null $year
     */
    public function __construct(int $vendorId = null, string $identifierType = null, $pid = null, string $title = null, string $year = null)
    {
        $this->vendorId = $vendorId;
        $this->identifierType = $identifierType;
        $this->pid = $pid;
        $this->title = $title;
        $this->year = $year;
    }

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
     * @return int
     */
    public function getVendorId(): int
    {
        return $this->vendorId;
    }

    /**
     * @param int $vendorId
     */
    public function setVendorId(int $vendorId): void
    {
        $this->vendorId = $vendorId;
    }

    /**
     * @return IdentifierType
     */
    public function getIdentifierType(): IdentifierType
    {
        return $this->identifierType;
    }

    /**
     * @param IdentifierType $identifierType
     */
    public function setIdentifierType(IdentifierType $identifierType): void
    {
        $this->identifierType = $identifierType;
    }

    /**
     * @return string
     */
    public function getPid(): string
    {
        return $this->pid;
    }

    /**
     * @param string $pid
     */
    public function setPid(string $pid): void
    {
        $this->pid = $pid;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return mixed
     */
    public function getYear()
    {
        return $this->year;
    }

    /**
     * @param mixed $year
     */
    public function setYear($year): void
    {
        $this->year = $year;
    }
}
