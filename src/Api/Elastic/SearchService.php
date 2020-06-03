<?php
/**
 * @file
 * ElasticSearch Service
 */

namespace App\Api\Elastic;

use App\Service\MetricsService;
use Elastica\Query;
use Elasticsearch\Client;

/**
 * Class SearchService.
 */
class SearchService implements SearchServiceInterface
{
    private $client;
    private $index;
    private $type;
    private $minImageSize;

    private $metricsService;

    /**
     * SearchService constructor.
     *
     * @param Client $client
     * @param string $envElasticIndex
     * @param string $envElasticType
     * @param int $envElasticMinImageSize
     * @param MetricsService $metricsService
     */
    public function __construct(Client $client, string $envElasticIndex, string $envElasticType, int $envElasticMinImageSize, MetricsService $metricsService)
    {
        $this->client = $client;
        $this->index = $envElasticIndex;
        $this->type = $envElasticType;
        $this->minImageSize = $envElasticMinImageSize;

        $this->metricsService = $metricsService;
    }

    /**
     * {@inheritdoc}
     */
    public function search(string $type, array $identifiers): array
    {
        $query = $this->buildElasticQuery($type, $identifiers);
        $body = $query->toArray();

        $startQueryTime = microtime(true);

        $documents = $this->client->search([
            'index' => $this->index,
            'type' => $this->type,
            'body' => $body,
        ]);

        $queryTime = microtime(true) - $startQueryTime;
        $this->metricsService->histogram('elastica_query_duration_seconds', 'Time used to run elasticsearch query', $queryTime, ['type' => 'rest']);

        return $this->filterResults($documents);
    }

    /**
     * Filter raw search result from ES request.
     *
     * @param array $results
     *   Raw search result array
     *
     * @return array
     *   The filtered results
     */
    private function filterResults(array $results): array
    {
        $hits = [];
        if (is_array($results['hits']['hits'])) {
            $results = $results['hits']['hits'];
            foreach ($results as $result) {
                $hits[] = $result['_source'];
            }
        }

        return $hits;
    }

    /**
     * Build Elastic query from IS types and IS identifiers.
     *
     * Create query in the form (id or id or ...) and (size > x) using bool and range filter query.
     *
     * @param string $type
     *   The type ('pid', 'isbn', etc) of identifiers given
     * @param array $identifiers
     *   Array of identifiers of {type}
     *
     * @return Query
     *   A new Elastica Query with terms and sort
     */
    private function buildElasticQuery(string $type, array $identifiers): Query
    {
        $innerQuery = new Query\BoolQuery();

        foreach ($identifiers as $identifier) {
            $identifierFieldTermQuery = new Query\Term();
            $identifierFieldTermQuery->setTerm('isIdentifier', $identifier);
            $innerQuery->addShould($identifierFieldTermQuery);
        }

        $outerBoolQuery = new Query\BoolQuery();
        $outerBoolQuery->addMust($innerQuery);

        $typeFieldTermQuery = new Query\Term();
        $typeFieldTermQuery->setTerm('isType', $type);
        $outerBoolQuery->addMust($typeFieldTermQuery);

        $range = new Query\Range();
        $range->addField('height', [
            'gte' => $this->minImageSize,
            'lt' => 'infinity',
        ]);
        $outerBoolQuery->addFilter($range);

        $query = new Query();
        $query->setQuery($outerBoolQuery);
        $query->setSize(count($identifiers));
        $query->setSort(['isIdentifier' => ['order' => 'asc']]);

        return $query;
    }
}
