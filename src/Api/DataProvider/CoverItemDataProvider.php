<?php

/**
 * @file
 * Cover item data provider.
 *
 * @see https://api-platform.com/docs/core/data-providers/
 */

namespace App\Api\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Api\Dto\Cover;
use App\Api\Dto\IdentifierInterface;

final class CoverItemDataProvider extends AbstractElasticSearchDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return Cover::class === $resourceClass;
    }

    /**
     * {@inheritdoc}
     */
    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?IdentifierInterface
    {
        $request = $this->requestStack->getCurrentRequest();
        $type = $this->getIdentifierType($context['request_uri']);

        $elasticQuery = $this->buildElasticQuery($type, [$id]);
        $search = $this->index->search($elasticQuery);
        $results = $search->getResults();

        $result = reset($results);

        if ($result) {
            $data = $result->getData();
            $identifier = $this->factory->createIdentifierDto($type, $data);
        }

        // @TODO Move logging logic to new EventListener an trigger on POST_READ, https://api-platform.com/docs/core/events/
        $this->logStatistics($type, [$id], $results, $request);

        // @TODO Move no hits logic to new EventListener an trigger on POST_READ, https://api-platform.com/docs/core/events/
        if (!$result) {
            $this->registerSearchNoHits($type, [$id]);
        }

        return $identifier ?? null;
    }
}
