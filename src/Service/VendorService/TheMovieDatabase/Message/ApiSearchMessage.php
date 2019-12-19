<?php

/**
 * @file
 * Contains ApiSearchMessage that contains data about a search from the data
 * well.
 */

namespace App\Service\VendorService\TheMovieDatabase\Message;

use App\Service\MoreInfoService\Types\IdentifierType;

/**
 * Class ApiSearchMessage.
 */
class ApiSearchMessage implements \JsonSerializable
{
    private $vendorId;
    private $identifierType;
    private $pid;
    private $title;
    private $year;
    private $originalYear;
    private $director;

    /**
     * ApiSearchMessage constructor.
     *
     * @param int|null    $vendorId
     *   The vendor id
     * @param string|null $identifierType
     *   The identifier type
     * @param string|null $pid
     *   The pid identifier
     * @param array       $meta
     *   A meta array containing title, year, originalYear, director
     */
    public function __construct(int $vendorId = null, string $identifierType = null, string $pid = null, array $meta = [])
    {
        $this->vendorId = $vendorId;
        $this->identifierType = $identifierType;
        $this->pid = $pid;
        $this->title = $meta['title'] ?? null;
        $this->year = $meta['year'] ?? null;
        $this->originalYear = $meta['originalYear'] ?? null;
        $this->director = $meta['director'] ?? null;
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

    /**
     * @return string
     */
    public function getOriginalYear(): ?string
    {
        return $this->originalYear;
    }

    /**
     * @param string $originalYear
     */
    public function setOriginalYear(string $originalYear): void
    {
        $this->originalYear = $originalYear;
    }

    /**
     * @return string
     */
    public function getDirector(): ?string
    {
        return $this->director;
    }

    /**
     * @param string $director
     */
    public function setDirector(string $director): void
    {
        $this->director = $director;
    }
}
