<?php
/**
 * @file
 * Data fixtures class to generate test data
 */

namespace App\DataFixtures;

use App\DataFixtures\Elastic\ElasticService;
use App\DataFixtures\Faker\Provider\SearchProvider;
use App\DataFixtures\Faker\Search;
use Faker\Factory;

/**
 * Class AppFixtures.
 */
class AppFixtures
{
    private const CREATE_TOTAL = 1000;
    private const BATCH_SIZE = 100;
    private const FAKER_SEED = 123456789;

    private $elasticService;

    /**
     * AppFixtures constructor.
     *
     * @param ElasticService $elasticService
     *   Service to update elasticsearch
     */
    public function __construct(ElasticService $elasticService)
    {
        $this->elasticService = $elasticService;
    }

    /**
     * Load app fixtures.
     */
    public function load(): void
    {
        $faker = Factory::create();
        $faker->seed(self::FAKER_SEED);
        $faker->addProvider(new SearchProvider($faker));

        $isbnNumbers = [];
        for ($i = 0; $i < 202; ++$i) {
            $isbnNumbers[] = $faker->isbn13;
        }
        $string = implode(',', $isbnNumbers);

        $searches = [];

        for ($i = 1; $i <= self::CREATE_TOTAL; ++$i) {
            $search = new Search();

            $search->setId($i);
            $search->setIsType($faker->isType);
            $search->setIsIdentifier($faker->isIdentifier($search->getIsType()));
            $search->setImageFormat($faker->imageFormat);
            $search->setImageUrl($faker->imageUrl);
            $search->setWidth($faker->width);
            $search->setHeight($faker->height);

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
