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
     */
    public function pid(): string
    {
        $libraryNumber = self::numberBetween(70000, 90000) * 10;

        return $libraryNumber.'-basis:'.self::randomNumber(8);
    }

    /**
     * Get a random faust number.
     *
     * @return string
     */
    public function faust(): string
    {
        return self::randomNumber(8);
    }

    /**
     * Get a random ISBN13 number.
     *
     * @return string
     */
    public function isbn(): string
    {
        return $this->generator->isbn13();
    }

    /**
     * Get a random identifier type.
     *
     * @return string
     */
    public function isType(): string
    {
        return self::randomElement(self::IS_TYPE_PROVIDER);
    }

    /**
     * Get a random identifier matching the given type.
     *
     * @param IdentifierType $type
     *
     * @return string|null
     *
     * @throws UnknownIsTypeException
     */
    public function isIdentifier(string $type): ?string
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
     * Get random image format.
     *
     * @return string
     */
    public function imageFormat(): string
    {
        return strtolower(self::randomElement(self::FORMAT_PROVIDER));
    }

    /**
     * Get random image url.
     *
     * @return string
     */
    public function imageUrl(): string
    {
        return 'https://res.cloudinary.com/dandigbib/image/upload/t_ddb_cover/v1576082092/foobar/'.$this->generator->isbn13().'.jpg';
    }

    /**
     * Get random height.
     *
     * @return int
     */
    public function height()
    {
        return self::numberBetween(128, 10000);
    }

    /**
     * get random width.
     *
     * @return int
     */
    public function width()
    {
        return self::numberBetween(128, 10000);
    }
}
