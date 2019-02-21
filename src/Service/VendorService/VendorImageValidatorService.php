<?php

namespace App\Service\VendorService;

use App\Entity\Source;
use App\Utils\CoverVendor\VendorImageItem;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;

/**
 * Class VendorImageValidatorService.
 */
class VendorImageValidatorService
{
    private $httpClient;

    /**
     * VendorImageValidatorService constructor.
     *
     * @param ClientInterface $httpClient
     */
    public function __construct(ClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Validate that remote image exists by sending a HTTP HEAD request.
     *
     * @param VendorImageItem $item
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function validateRemoteImage(VendorImageItem $item): void
    {
        try {
            $head = $this->httpClient->request('HEAD', $item->getOriginalFile());

            $contentLengthArray = $head->getHeader('Content-Length');
            $lastModifiedArray = $head->getHeader('Last-Modified');

            $timezone = new \DateTimeZone('UTC');
            $lastModified = \DateTime::createFromFormat('D, d M Y H:i:s \G\M\T', array_shift($lastModifiedArray), $timezone);

            $item->setOriginalContentLength(array_shift($contentLengthArray));
            $item->setOriginalLastModified($lastModified);

            // Some images exists (return 200) but have no content
            $found = $item->getOriginalContentLength() > 0;
            $item->setFound($found);
        } catch (ClientException $exception) {
            $item->setFound(false);
        }
    }

    /**
     * Check if a remote image has been updated since we fetched the source.
     *
     * @param VendorImageItem $item
     * @param Source $source
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function isRemoteImageUpdated(VendorImageItem $item, Source $source): void
    {
        $this->validateRemoteImage($item);
        $item->setUpdated(false);

        if ($item->isFound()) {
            if ($item->getOriginalLastModified() != $source->getOriginalLastModified() ||
                $item->getOriginalContentLength() !== $source->getOriginalContentLength()) {
                $item->setUpdated(true);
            }
        }
    }
}
