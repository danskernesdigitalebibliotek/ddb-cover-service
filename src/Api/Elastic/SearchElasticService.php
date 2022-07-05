<?php
/**
 * @file
 * ElasticSearch Service
 */

namespace App\Api\Elastic;

use Elastica\Query;
use Elasticsearch\Client;
use ItkDev\MetricsBundle\Service\MetricsService;

/**
 * Class SearchService.
 */
class SearchElasticService implements SearchServiceInterface
{
    private string$index;
    private int $minImageSize;

    /**
     * SearchService constructor.
     *
     * @param Client $client
     * @param string $bindElasticIndex
     * @param int $bindElasticMinImageSize
     * @param MetricsService $metricsService
     */
    public function __construct(
        private readonly Client $client,
        private readonly MetricsService $metricsService,
        string $bindElasticIndex,
        int $bindElasticMinImageSize)
    {
        $this->index = $bindElasticIndex;
        $this->minImageSize = $bindElasticMinImageSize;
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
        ]);
        $outerBoolQuery->addFilter($range);

        $query = new Query();
        $query->setQuery($outerBoolQuery);
        $query->setSize(count($identifiers));
        $query->setSort(['isIdentifier' => ['order' => 'asc']]);

        return $query;
    }
}
