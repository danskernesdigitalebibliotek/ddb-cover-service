<?php
/**
 * @file
 * Faker provider for Search objects.
 */

namespace App\DataFixtures\Faker\Provider;

use App\DataFixtures\Faker\Exception\UnknownIsTypeException;
use App\Utils\Types\IdentifierType;
use Faker\Provider\Base as BaseProvider;

/**
 * Class SearchProvider.
 */
final class SearchProvider extends BaseProvider
{
    private const IS_TYPE_PROVIDER = [IdentifierType::PID, IdentifierType::ISBN, IdentifierType::FAUST];
    private const FORMAT_PROVIDER = ['JPEG', 'PNG'];

    /**
     * Get a random pid number.
     *
     * @return string
     *   A random but pseudo realistic pid identifier
     */
    public function pid(): string
    {
        $libraryNumber = self::numberBetween(70000, 90000) * 10;

        return $libraryNumber.'-basis:'.self::randomNumber(8);
    }

    /**
     * Get a random faust number.
     *
     * @return string A random but pseudo realistic faust identifier
     */
    public function faust(): string
    {
        return (string) self::randomNumber(8);
    }

    /**
     * Get a random ISBN13 number.
     *
     * @return string
     *   A random but pseudo realistic ISBN13 identifier
     */
    public function isbn(): string
    {
        return $this->generator->isbn13();
    }

    /**
     * Get a random identifier type ('faust' | 'isbn' | 'pid').
     *
     * @return string
     *   A random identifier type
     */
    public function isType(): string
    {
        return self::randomElement(self::IS_TYPE_PROVIDER);
    }

    /**
     * Get a random identifier matching the given type.
     *
     * @param string $type
     *   The identifier type to generate identifier for
     *
     * @return string
     *   A random but pseudo realistic identifier
     *
     * @throws UnknownIsTypeException
     */
    public function isIdentifier(string $type): string
    {
        switch ($type) {
            case IdentifierType::FAUST:
                return $this->faust();
            case IdentifierType::ISBN:
                return $this->isbn();
            case IdentifierType::PID:
                return $this->pid();
            default:
                throw new UnknownIsTypeException('Cannot create fake data for unknown identifier '.$type);
        }
    }

    /**
     * Get random image format ('JPEG' | 'PNG').
     *
     * @return string
     *   A random image format
     */
    public function imageFormat(): string
    {
        return strtolower(self::randomElement(self::FORMAT_PROVIDER));
    }

    /**
     * Get random image url for the cloudinary CDN.
     *
     * @return string
     *   A random realistic CDN URL
     */
    public function imageUrl(): string
    {
        return 'https://res.cloudinary.com/dandigbib/image/upload/v1543609481/bogportalen.dk/'.$this->generator->isbn13().'.jpg';
    }

    /**
     * Get random height.
     *
     * @return int
     *   A random height, >=128, <=10000
     */
    public function height(): int
    {
        return self::numberBetween(128, 10000);
    }

    /**
     * Get random width.
     *
     * @return int
     *   A random width, >=128, <=10000
     */
    public function width(): int
    {
        return self::numberBetween(128, 10000);
    }
}
