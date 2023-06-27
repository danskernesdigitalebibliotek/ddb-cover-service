<?php
/**
 * @file
 * Data fixtures class to generate test data
 */

namespace App\DataFixtures;

use App\DataFixtures\Elastic\ElasticService;
use App\DataFixtures\Faker\Provider\SearchProvider;
use App\DataFixtures\Faker\Search;
use App\Utils\Types\IdentifierType;
use Faker\Factory;

/**
 * Class AppFixtures.
 */
class AppFixtures
{
    private const CREATE_TOTAL = 1000;
    private const BATCH_SIZE = 100;
    private const FAKER_SEED = 123456789;

    /**
     * AppFixtures constructor.
     *
     * @param ElasticService $elasticService
     *   Service to update elasticsearch
     */
    public function __construct(
        private readonly ElasticService $elasticService
    ) {
    }

    /**
     * Load app fixtures.
     */
    public function load(): void
    {
        $faker = Factory::create();
        $faker->seed(self::FAKER_SEED);
        $faker->addProvider(new SearchProvider($faker));

        $searches = [];

        for ($i = 1; $i <= self::CREATE_TOTAL; ++$i) {
            $search = new Search();

            $search->setId($i);

            // Ensure that examples used in OpenApi return results
            if (1 === $i) {
                $search->setIsType(IdentifierType::PID);
                $search->setIsIdentifier('870970-basis:29862885');
                $search->setHeight(2000);
            } elseif (2 === $i) {
                $search->setIsType(IdentifierType::ISBN);
                $search->setIsIdentifier('9785341366046');
                $search->setHeight(2000);
            } elseif (3 === $i) {
                $search->setIsType(IdentifierType::PID);
                $search->setIsIdentifier('870970-basis:27992625');
                $search->setHeight(500);
            } elseif (4 === $i) {
                $search->setIsType(IdentifierType::ISBN);
                $search->setIsIdentifier('9799913633580');
                $search->setHeight(500);
            } else {
                $search->setIsType($faker->isType());
                $search->setIsIdentifier($faker->isIdentifier($search->getIsType()));
                $search->setHeight($faker->height());
                $search->setGenericCover($faker->generic());
            }

            $search->setImageFormat($faker->imageFormat());
            $search->setImageUrl($faker->imageUrl());
            $search->setWidth($faker->width());

            $searches[] = $search;

            if (0 === $i % self::BATCH_SIZE) {
                $this->elasticService->index(...$searches);
                $searches = [];
            }
        }

        if (!empty($searches)) {
            $this->elasticService->index(...$searches);
        }
    }
}
