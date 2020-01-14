<?php
/**
 * @file
 * Convert 'RbDigital' urls to pointing to medium sized images from pointing to small sized.
 */

namespace App\Service\VendorService\RbDigital\DataConverter;

/**
 * Class RbDigitalBooksPublicUrlConverter.
 */
class RbDigitalMagazinesPublicUrlConverter
{
    /**
     * Remove 'format' and other parameters from URLs to get original size.
     *
     * @param array $list
     *   An array of key => urls to be converted
     */
    public static function convertArrayValues(array &$list): void
    {
        foreach ($list as $key => &$value) {
            $value = self::convertSingleUrl($value);
        }
    }

    /**
     * Remove 'format' and other parameters from URL to get original size.
     *
     * @param string $url
     *   The original image url
     *
     * @return string
     *   The image url with all parameters removed
     */
    public static function convertSingleUrl(string $url): string
    {
        return strtok($url, '?');
    }
}
