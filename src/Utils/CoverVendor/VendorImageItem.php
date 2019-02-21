<?php

/**
 * @file
 * Wrapper for information returned from vendor image host.
 */

namespace App\Utils\CoverVendor;

class VendorImageItem
{
    private $found = false;
    private $updated = false;
    private $vendor;
    private $originalFile;
    private $originalLastModified;
    private $originalContentLength;

    public function __toString()
    {
        $output = [];

        $output[] = str_repeat('-', 42);
        $output[] = 'Vendor: '.$this->getVendor();
        $output[] = 'Original file: '.$this->getOriginalFile();
        $output[] = str_repeat('-', 42);

        return implode("\n", $output);
    }

    /**
     * @return bool
     */
    public function isFound(): bool
    {
        return $this->found;
    }

    /**
     * @param bool $found
     *
     * @return VendorImageItem
     */
    public function setFound(bool $found): self
    {
        $this->found = $found;

        return $this;
    }

    /**
     * @return bool
     */
    public function isUpdated(): bool
    {
        return $this->updated;
    }

    /**
     * @param bool $updated
     *
     * @return VendorImageItem
     */
    public function setUpdated(bool $updated): self
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * @return string
     */
    public function getVendor(): string
    {
        return $this->vendor;
    }

    /**
     * @param $vendor
     *
     * @return VendorImageItem
     */
    public function setVendor($vendor): self
    {
        $this->vendor = $vendor;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getOriginalFile(): string
    {
        return $this->originalFile;
    }

    /**
     * @param string $originalFile
     *
     * @return VendorImageItem
     */
    public function setOriginalFile(string $originalFile): self
    {
        $this->originalFile = $originalFile;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getOriginalLastModified(): \DateTime
    {
        return $this->originalLastModified;
    }

    /**
     * @param mixed $originalLastModified
     *
     * @return VendorImageItem
     */
    public function setOriginalLastModified(\DateTime $originalLastModified): self
    {
        $this->originalLastModified = $originalLastModified;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getOriginalContentLength(): int
    {
        return $this->originalContentLength;
    }

    /**
     * @param mixed $originalContentLength
     *
     * @return VendorImageItem
     */
    public function setOriginalContentLength(int $originalContentLength): self
    {
        $this->originalContentLength = $originalContentLength;

        return $this;
    }
}
