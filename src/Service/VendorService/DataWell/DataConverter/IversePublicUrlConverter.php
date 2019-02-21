<?php
/**
 * @file
 * Convert 'Iverse' urls from pointing to medium sized images to pointing large size.
 */

namespace App\Service\VendorService\DataWell\DataConverter;

/**
 * Class IversePublicUrlConverter.
 */
class IversePublicUrlConverter
{
    private const PADMEDIUM_URL_STRING = '/iverse_public/store/cover/padmedium/';
    private const PADLARGE_URL_STRING = '/iverse_public/store/cover/padlarge/';

    /**
     * Convert array value 'Iverse' URLs from 'medium' to 'large'.
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
     * Convert 'Iverse' URL from 'medium' to 'large'.
     *
     * @param string $url
     *   The 'padmedium' image url
     *
     * @return string
     *   The 'padlarge' image url
     */
    public static function convertSingleUrl(string $url): string
    {
        $padlarge = str_replace(self::PADMEDIUM_URL_STRING, self::PADLARGE_URL_STRING, $url);

        return $padlarge;
    }
}
