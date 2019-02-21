<?php

namespace App\DataFixtures\Faker\Provider;

use App\Utils\Types\IdentifierType;
use Faker\Provider\Base as BaseProvider;

final class SearchProvider extends BaseProvider
{
    private const IS_TYPE_PROVIDER = [IdentifierType::PID, IdentifierType::ISBN];

    public function pid()
    {
        $libraryNumber = self::numberBetween(70000, 90000) * 10;

        return $libraryNumber.'-basis:'.self::randomNumber(8);
    }

    public function faust()
    {
        return self::randomNumber(8);
    }

    public function isTYpe()
    {
        return self::randomElement(self::IS_TYPE_PROVIDER);
    }
}
