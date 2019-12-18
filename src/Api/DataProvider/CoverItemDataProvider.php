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
        $request = $this->requestStack->getCurrentRequest();
        $type = $this->getIdentifierType($context['request_uri']);

        $query = $this->buildElasticQuery($type, [$id]);

        // Note that we here don't uses the elastica request function to post the request to elasticsearch has we have
        // had strange performance issues with it. We get information about the index and create the curl call by hand.
        // We can do this as we now the complete setup and what should be taken into consideration.
        $index = $this->index->getIndex();
        $connection = $index->getClient()->getConnection();
        $path = $index->getName().'/search/_search';
        $url = $connection->hasConfig('url') ? $connection->getConfig('url') : '';
        $json_query = JSON::stringify($query->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $startQueryTime = microtime(true);
        $ch = curl_init($url.$path);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_query);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: '.strlen($json_query),
        ]);
        $response = curl_exec($ch);
        $queryTime = microtime(true) - $startQueryTime;

        $results = JSON::parse($response);
        $results = $this->filterResults($results);

        $this->metricsService->counter('rest_requests_total', 'Total rest requests', 1, ['type' => 'single']);
        $this->metricsService->histogram('elastica_query_duration_seconds', 'Time used to run elasticsearch query', $queryTime, ['type' => 'rest']);

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
