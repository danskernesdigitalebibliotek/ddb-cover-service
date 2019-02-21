<?php

namespace App\DataFixtures\Faker\Provider;

use App\Utils\Types\IdentifierType;
use Faker\Provider\Base as BaseProvider;
use Faker\Provider\DateTime;

final class SourceProvider extends BaseProvider
{
    private const MATCH_TYPE_PROVIDER = [IdentifierType::ISBN, IdentifierType::ISSN, IdentifierType::ISMN];

    public function date()
    {
        return DateTime::dateTimeBetween('- 20 years', 'now');
    }

    public function matchId()
    {
        return $this->generator->ean13();
    }

    public function matchType()
    {
        return self::randomElement(self::MATCH_TYPE_PROVIDER);
    }
}
