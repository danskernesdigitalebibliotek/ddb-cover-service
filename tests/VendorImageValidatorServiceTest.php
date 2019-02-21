<?php

/**
 * @file
 * Test cases for the Vendor image validation service.
 */

namespace Tests;

use App\Entity\Source;
use App\Service\VendorService\VendorImageValidatorService;
use App\Utils\CoverVendor\VendorImageItem;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class VendorImageValidatorServiceTest extends TestCase
{
    private $lastModified = 'Wed, 05 Dec 2018 07:28:00 GMT';
    private $contentLength = 12345;
    private $url = 'http://test.cover/image.jpg';

    /**
     * Test that remote image exists.
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testValidateRemoteImage()
    {
        $client = $this->mockHttpClient(200, [
            'Content-Length' => $this->contentLength,
            'Last-Modified' => $this->lastModified,
        ], '');

        $item = new VendorImageItem();
        $item->setOriginalFile($this->url);

        $service = new VendorImageValidatorService($client);
        $service->validateRemoteImage($item);

        $this->assertEquals(true, $item->isFound());
        $this->assertEquals($this->lastModified, $item->getOriginalLastModified()->format('D, d M Y H:i:s \G\M\T'));
        $this->assertEquals($this->contentLength, $item->getOriginalContentLength());
        $this->assertEquals($this->url, $item->getOriginalFile());
    }

    /**
     * Test that missing image is detected.
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testValidateRemoteImageMissing()
    {
        $client = $this->mockHttpClient(404, [], '');

        $item = new VendorImageItem();
        $item->setOriginalFile($this->url);

        $service = new VendorImageValidatorService($client);
        $service->validateRemoteImage($item);

        $this->assertEquals(false, $item->isFound());
    }

    /**
     * Test that image is not modified.
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testIsRemoteImageUpdatedNotModified()
    {
        $client = $this->mockHttpClient(200, [
            'Content-Length' => $this->contentLength,
            'Last-Modified' => $this->lastModified,
        ], '');

        $timezone = new \DateTimeZone('UTC');
        $lastModifiedDateTime = \DateTime::createFromFormat('D, d M Y H:i:s \G\M\T', $this->lastModified, $timezone);

        $item = new VendorImageItem();
        $item->setOriginalFile($this->url)
            ->setOriginalContentLength($this->contentLength)
            ->setOriginalLastModified($lastModifiedDateTime);

        $source = new Source();
        $source->setOriginalFile($this->url)
            ->setOriginalContentLength($this->contentLength)
            ->setOriginalLastModified($lastModifiedDateTime);

        $service = new VendorImageValidatorService($client);
        $service->isRemoteImageUpdated($item, $source);

        $this->assertEquals(false, $item->isUpdated());
    }

    /**
     * Test that changes in the image is detected.
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testIsRemoteImageUpdatedModified()
    {
        $client = $this->mockHttpClient(200, [
            'Content-Length' => $this->contentLength,
            'Last-Modified' => $this->lastModified,
        ], '');

        $timezone = new \DateTimeZone('UTC');
        $lastModifiedDateTime = \DateTime::createFromFormat('D, d M Y H:i:s \G\M\T', $this->lastModified, $timezone);

        $item = new VendorImageItem();
        $item->setOriginalFile($this->url)
            ->setOriginalContentLength($this->contentLength)
            ->setOriginalLastModified($lastModifiedDateTime);

        $source = new Source();
        $source->setOriginalFile($this->url)
            ->setOriginalContentLength($this->contentLength + 200)
            ->setOriginalLastModified($lastModifiedDateTime);

        $service = new VendorImageValidatorService($client);
        $service->isRemoteImageUpdated($item, $source);

        $this->assertEquals(true, $item->isFound());
        $this->assertEquals(true, $item->isUpdated());
    }

    /**
     * Helper function to mock http client.
     *
     * @param $status
     *   HTTP status code
     * @param $headers
     *   HTTP headers
     * @param $body
     *   Body content
     *
     * @return Client
     */
    private function mockHttpClient($status, $headers, $body)
    {
        $mock = new MockHandler();
        $mock->append(new Response($status, $headers, $body));

        $handler = HandlerStack::create($mock);

        return new Client(['handler' => $handler]);
    }
}
