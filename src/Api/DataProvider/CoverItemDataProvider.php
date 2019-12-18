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
use Elastica\JSON;
use Elastica\Request;

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
        $this->metricsService->counter('rest_requests_total', 'Total rest requests', 1, ['type' => 'single']);

        $request = $this->requestStack->getCurrentRequest();
        $type = $this->getIdentifierType($context['request_uri']);

        $query = $this->buildElasticQuery($type, [$id]);
        $results = $this->search($query);

        // This data provider should always return only one item.
        $result = reset($results);
        if ($result) {
            $identifier = $this->factory->createIdentifierDto($type, $result);
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
