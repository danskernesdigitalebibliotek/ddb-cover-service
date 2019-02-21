<?php

namespace App\DataFixtures\Faker\Provider;

use Faker\Provider\Base as BaseProvider;
use Faker\Provider\Miscellaneous;

final class ImageProvider extends BaseProvider
{
    private const FORMAT_PROVIDER = ['JPEG', 'GIF', 'PNG', 'TIFF', 'RAW', 'BMP'];
    private const COVER_STORE_URL_PROVIDER = ['https://images.bogportalen.dk/images/'];

    public function orginalFile($format)
    {
        switch ($format) {
            case 'JPEG':
                $fileExt = 'jpg';
                break;
            case 'TIFF':
                $fileExt = 'tif';
                break;
            default:
                $fileExt = strtolower($format);
        }

        return Miscellaneous::sha1().'.'.$fileExt;
    }

    public function originalImageFormat()
    {
        return strtolower(self::randomElement(self::FORMAT_PROVIDER));
    }

    public function size(int $width, int $height)
    {
        return self::numberBetween(128, 10000);
    }

    public function height()
    {
        return self::numberBetween(128, 10000);
    }

    public function width()
    {
        return self::numberBetween(128, 10000);
    }

    public function coverStoreURL()
    {
        return self::randomElement(self::COVER_STORE_URL_PROVIDER).Miscellaneous::sha1().'.jpg';
    }
}
