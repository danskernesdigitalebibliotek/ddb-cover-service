<?php

/**
 * @file
 * Search filter for elastic.
 *
 * @see https://api-platform.com/docs/core/filters/
 */

namespace App\Api\Filter;

use ApiPlatform\Core\Api\FilterInterface;

class SearchFilter implements FilterInterface
{
    /**
     * @var string Exact matching
     */
    const STRATEGY_EXACT = 'exact';

    protected $properties;

    /**
     * SearchFilter constructor.
     *
     * @param array|null $properties
     */
    public function __construct(array $properties = null)
    {
        $this->properties = $properties;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(string $resourceClass): array
    {
        $description = [];

        $properties = $this->properties;

        foreach ($properties as $property => $strategy) {
            $filterParameterNames = [
                $property,
                $property.'[]',
            ];

            foreach ($filterParameterNames as $filterParameterName) {
                $description[$filterParameterName] = [
                    'property' => $property,
                    'type' => 'string',
                    'required' => false,
                    'strategy' => self::STRATEGY_EXACT,
                    'is_collection' => '[]' === substr($filterParameterName, -2),
                ];
            }
        }

        return $description;
    }
}
