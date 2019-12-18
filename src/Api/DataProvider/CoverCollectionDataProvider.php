<?php
/**
 * @file
 * Cover collection data provider.
 *
 * @see https://api-platform.com/docs/core/data-providers/
 */

namespace App\Api\DataProvider;

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Api\Dto\Cover;
use App\Api\Exception\RequiredParameterMissingException;
use Elastica\JSON;
use Symfony\Component\HttpFoundation\Request;

final class CoverCollectionDataProvider extends AbstractElasticSearchDataProvider implements CollectionDataProviderInterface, RestrictedDataProviderInterface
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
    public function getCollection(string $resourceClass, string $operationName = null): \Traversable
    {
        $request = $this->requestStack->getCurrentRequest();

        $identifierType = $this->getIdentifierType($request->getPathInfo());

        $isIdentifiers = $this->getIdentifiers($request);

        $query = $this->buildElasticQuery($identifierType, $isIdentifiers);

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

        $this->metricsService->counter('rest_requests_total', 'Total rest requests', 1, ['type' => 'collection']);
        $this->metricsService->histogram('elastica_query_duration_seconds', 'Time used to run elasticsearch query', $queryTime, ['type' => 'rest']);

        $foundIdentifiers = [];

        foreach ($results as $result) {
            $foundIdentifiers[] = $result['isIdentifier'];

            yield $this->factory->createIdentifierDto($identifierType, $result);
        }

        // @TODO Move logging logic to new EventListener an trigger on POST_READ, https://api-platform.com/docs/core/events/
        $this->logStatistics($identifierType, $isIdentifiers, $results, $request);

        // @TODO Move no hits logic to new EventListener an trigger on POST_READ, https://api-platform.com/docs/core/events/
        $notFoundIdentifiers = array_diff($isIdentifiers, $foundIdentifiers);
        if ($notFoundIdentifiers) {
            $this->registerSearchNoHits($identifierType, $notFoundIdentifiers);
        }
    }

    /**
     * Get IS identifiers from request parameters.
     *
     * @param Request $request
     *   The Symfony request
     *
     * @return array
     *   Array of identifiers given in the request parameters
     *
     * @throws RequiredParameterMissingException
     *   If the 'id' parameter is not found in the request
     */
    private function getIdentifiers(Request $request): array
    {
        $identifiers = $request->query->get('id');

        if (!$identifiers) {
            throw new RequiredParameterMissingException('The "id" parameter is required');
        }

        $isIdentifiers = explode(',', $identifiers);
        $isIdentifiers = \is_array($isIdentifiers) ? $isIdentifiers : [$isIdentifiers];

        return $isIdentifiers;
    }
}
