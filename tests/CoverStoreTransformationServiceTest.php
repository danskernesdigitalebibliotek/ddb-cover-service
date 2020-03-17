<?php

/**
 * @file
 * Test cases for the Cover store transformation service.
 */

namespace Tests;

use App\Exception\CoverStoreTransformationException;
use App\Service\CoverStore\CloudinaryTransformationService;
use App\Service\CoverStore\CoverStoreTransformationInterface;
use PHPUnit\Framework\TestCase;

/**
 * Class CoverStoreTransformationServiceTest.
 */
class CoverStoreTransformationServiceTest extends TestCase
{
    private $url = 'https://res.cloudinary.com/dandigbib/image/upload/v1544766159/publizon/9788711672051.png';

    /**
     * Test that "default" transformation.
     */
    public function testDefaultTransformation()
    {
        $output = 'https://res.cloudinary.com/dandigbib/image/upload/t_ddb_cover/v1544766159/publizon/9788711672051.jpg';
        $service = $this->getService();
        $this->assertEquals($service->transform($this->url, 1080, 1920), $output);
    }

    /**
     * Test that named transformation.
     */
    public function testNamedTransformation()
    {
        $output = 'https://res.cloudinary.com/dandigbib/image/upload/t_ddb_cover_small/v1544766159/publizon/9788711672051.jpg';
        $service = $this->getService();
        $this->assertEquals($service->transform($this->url, 1080, 1920, 't1'), $output);
    }

    /**
     * Test that extension only transformation.
     */
    public function testExtensionOnlyTransformation()
    {
        $output = 'https://res.cloudinary.com/dandigbib/image/upload/v1544766159/publizon/9788711672051.jpg';
        $service = $this->getService();
        $this->assertEquals($service->transform($this->url, 1080, 1920, 't3'), $output);
    }

    /**
     * Test that named transformation without extension.
     */
    public function testNamedNoExtensionTransformation()
    {
        $output = 'https://res.cloudinary.com/dandigbib/image/upload/t_ddb_cover_medium/v1544766159/publizon/9788711672051.png';
        $service = $this->getService();
        $this->assertEquals($service->transform($this->url, 1080, 1920, 't2'), $output);
    }

    /**
     * Test that all transformation is handled.
     */
    public function testAllTransformation()
    {
        $output = [
            'original' => $this->url,
            'default' => 'https://res.cloudinary.com/dandigbib/image/upload/t_ddb_cover/v1544766159/publizon/9788711672051.jpg',
            't1' => 'https://res.cloudinary.com/dandigbib/image/upload/t_ddb_cover_small/v1544766159/publizon/9788711672051.jpg',
            't2' => 'https://res.cloudinary.com/dandigbib/image/upload/t_ddb_cover_medium/v1544766159/publizon/9788711672051.png',
            't3' => 'https://res.cloudinary.com/dandigbib/image/upload/v1544766159/publizon/9788711672051.jpg',
        ];

        $service = $this->getService();
        $this->assertEquals($service->transformAll($this->url, 1080, 1920), $output);
    }

    /**
     * Test that transformation with small sizes returns null.
     */
    public function testAllTransformationWithSmallSize()
    {
        $output = [
            'original' => $this->url,
            'default' => null,
            't1' => 'https://res.cloudinary.com/dandigbib/image/upload/t_ddb_cover_small/v1544766159/publizon/9788711672051.jpg',
            't2' => 'https://res.cloudinary.com/dandigbib/image/upload/t_ddb_cover_medium/v1544766159/publizon/9788711672051.png',
            't3' => 'https://res.cloudinary.com/dandigbib/image/upload/v1544766159/publizon/9788711672051.jpg',
        ];

        $service = $this->getService();
        $this->assertEquals($service->transformAll($this->url, 1080, 200), $output);
    }

    /**
     * Test get available formats..
     */
    public function testGetFormatsTransformation()
    {
        $service = $this->getService();
        $this->assertEquals($service->getFormats(), $this->getTransformations());
    }

    /**
     * Test that exception is throw when exception is unknown.
     */
    public function testUnknownTransformation()
    {
        $this->expectException(CoverStoreTransformationException::class);
        $service = $this->getService();
        $service->transform($this->url, 1080, 1920, 'fake');
    }

    /**
     * Get the transformation service.
     *
     * @return coverStoreTransformationInterface
     *   The service
     */
    private function getService(): CoverStoreTransformationInterface
    {
        return new CloudinaryTransformationService($this->getTransformations());
    }

    /**
     * Mock configuration.
     *
     * @return array
     *   Configuration array as build by the YML file
     */
    private function getTransformations()
    {
        return [
            'original' => [],
            'default' => [
                'transformation' => 't_ddb_cover',
                'extension' => 'jpg',
                'size' => 1000,
            ],
            't1' => [
                'transformation' => 't_ddb_cover_small',
                'extension' => 'jpg',
                'size' => 100,
            ],
            't2' => [
                'transformation' => 't_ddb_cover_medium',
                'size' => 200,
            ],
            't3' => [
                'extension' => 'jpg',
                'size' => 300,
            ],
      ];
    }
}
