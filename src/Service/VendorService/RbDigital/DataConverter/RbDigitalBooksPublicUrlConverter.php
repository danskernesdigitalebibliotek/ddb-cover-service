<?php
/**
 * @file
 * Convert 'RbDigital' urls to pointing to medium sized images from pointing to small sized.
 */

namespace App\Service\VendorService\RbDigital\DataConverter;

/**
 * Class RbDigitalBooksPublicUrlConverter.
 */
class RbDigitalBooksPublicUrlConverter
{
    private const SMALL_URL_STRING = '_image_95x140.jpg';
    private const MEDIUM_URL_STRING = '_image_148x230.jpg';

    /**
     * Convert array value 'RBDigital' URLs from '95x140' to '148x230'.
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
     * Convert 'RBDigital' URL from 'small' to 'medium'.
     *
     * @param string $url
     *   The '95x140' image url
     *
     * @return string
     *   The '148x230' image url
     */
    public static function convertSingleUrl(string $url): string
    {
        return str_replace(self::SMALL_URL_STRING, self::MEDIUM_URL_STRING, $url);
    }
}
