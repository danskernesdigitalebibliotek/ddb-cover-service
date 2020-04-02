<?php

/**
 * @file
 * Abstract ElasticSearch data provider. Defines base functionality
 * for querying and statistics.
 *
 * @see https://api-platform.com/docs/core/data-providers/
 */

namespace App\Api\DataProvider;

use App\Api\Exception\UnknownIdentifierTypeException;
use App\Api\Factory\IdentifierFactory;
use App\Service\MetricsService;
use App\Service\NoHitService;
use App\Service\StatsLoggingService;
use App\Utils\Types\IdentifierType;
use App\Utils\Types\NoHitItem;
use Elastica\JSON;
use Elastica\Query;
use Elastica\Type;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class AbstractElasticSearchDataProvider.
 */
abstract class AbstractElasticSearchDataProvider
{
    protected $index;
    protected $statsLoggingService;
    protected $metricsService;
    protected $requestStack;
    protected $dispatcher;
    protected $factory;
    protected $noHitService;
    protected $logger;
    protected $minImageSize;

    /**
     * SearchCollectionDataProvider constructor.
     *
     * @param Type $index
     *   Elastica index search type
     * @param RequestStack $requestStack
     *   Symfony request stack
     * @param StatsLoggingService $statsLoggingService
     *   Statistics logging service
     * @param MetricsService $metricsService
     *   Log metric information
     * @param EventDispatcherInterface $dispatcher
     *   Symfony Event Dispatcher
     * @param IdentifierFactory $factory
     *   Factory to create Identifier Data Transfer Objects (DTOs)
     * @param NoHitService $noHitService
     *   Service for registering no hits
     * @param loggerInterface $logger
     *   Standard logger
     * @param ParameterBagInterface $params
     *   Access to environment variables
     */
    public function __construct(Type $index, RequestStack $requestStack, StatsLoggingService $statsLoggingService, MetricsService $metricsService, EventDispatcherInterface $dispatcher, IdentifierFactory $factory, NoHitService $noHitService, LoggerInterface $logger, ParameterBagInterface $params)
    {
        $this->index = $index;
        $this->requestStack = $requestStack;
        $this->statsLoggingService = $statsLoggingService;
        $this->metricsService = $metricsService;
        $this->dispatcher = $dispatcher;
        $this->factory = $factory;
        $this->noHitService = $noHitService;
        $this->logger = $logger;

        try {
            $this->minImageSize = $params->get('elastic.min.image.size');
        } catch (ParameterNotFoundException $e) {
            // Default to show all images regardless of size.
            $this->minImageSize = 0;
        }
    }

    /**
     * Send event to register identifiers that gave no search results.
     *
     * @param string $type
     *   The type ('pid', 'isbn', etc) of identifiers given
     * @param array $identifiers
     *   Array of identifiers of {type}
     */
    protected function registerSearchNoHits(string $type, array $identifiers): void
    {
        $noHits = [];

        foreach ($identifiers as $identifier) {
            $noHits[] = new NoHitItem($type, $identifier);
        }

        if (!empty($noHits)) {
            $this->metricsService->counter('no_hit_event_duration_seconds', 'Total number of no-hits', count($noHits), ['type' => 'rest']);

            $this->noHitService->registerNoHits($noHits);
        }
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
    protected function buildElasticQuery(string $type, array $identifiers): Query
    {
        $innerQuery = new Query\BoolQuery();

        foreach ($identifiers as $identifier) {
            $identifierFieldTermQuery = new Query\Term();
            $identifierFieldTermQuery->setTerm('isIdentifier', $identifier);
            $innerQuery->addShould($identifierFieldTermQuery);
        }

        $outerBoolQuery = new Query\BoolQuery();
        $outerBoolQuery->addMust($innerQuery);

        $range = new Query\Range();
        $range->addField('height', [
            'gte' => $this->minImageSize,
            'lt' => 'infinity',
        ]);
        $outerBoolQuery->addFilter($range);

        $query = new Query();
        $query->setQuery($outerBoolQuery);
        $query->setSize(count($identifiers));

        return $query;
    }

    /**
     * Execute search query.
     *
     * @param Query $query
     *   The search query
     *
     * @return array
     *   Search results found in ES
     */
    protected function search(Query $query)
    {
        $results = [];

        // Note that we here don't uses the elastica request function to post the request to elasticsearch because we
        // have had strange performance issues with it. We get information about the index and create the curl call by
        // hand. We can do this as we know the complete setup and what should be taken into consideration.
        $index = $this->index->getIndex();
        $connection = $index->getClient()->getConnection();
        $path = $index->getName().'/search/_search';
        $url = $connection->hasConfig('url') ? $connection->getConfig('url') : '';
        $jsonQuery = JSON::stringify($query->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $startQueryTime = microtime(true);
        $ch = curl_init($url.$path);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonQuery);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: '.strlen($jsonQuery),
        ]);
        $response = curl_exec($ch);
        $queryTime = microtime(true) - $startQueryTime;

        if (false === $response) {
            $this->logger->error('Curl ES query error: '.curl_error($ch));
        } else {
            $results = JSON::parse($response);
            $results = $this->filterResults($results);
        }
        curl_close($ch);

        $this->metricsService->histogram('elastica_query_duration_seconds', 'Time used to run elasticsearch query', $queryTime, ['type' => 'rest']);

        return $results;
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
    protected function filterResults(array $results): array
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
     * Get image URLs from search result.
     *
     * @param array $results
     *   An array of result from an Elastica search
     *
     * @return array
     *   An array of image urls strings from the results
     */
    protected function getImageUrls(array $results): array
    {
        $urls = [];
        foreach ($results as $result) {
            if (isset($result['isIdentifier'])) {
                $urls[$result['isIdentifier']] = $result['imageUrl'];
            }
        }

        return $urls;
    }

    /**
     * Get the identifier type from the request uri.
     *
     * @param string $requestUri
     *   The URI of the request
     *
     * @return string
     *   The identifier type (e.g. 'pid', 'isbn', etc).
     *
     * @throws UnknownIdentifierTypeException
     *   If identifier is not defined in App\Utils\Types\IdentifierType
     * @throws \ReflectionException
     */
    protected function getIdentifierType(string $requestUri): string
    {
        // This is part of the hack to support parameters in path.
        // Path for covers is /api/cover/{type}/{id}
        $split = explode('/', $requestUri);
        $type = $split[3];

        if (in_array($type, IdentifierType::getTypeList(), true)) {
            return $type;
        }

        throw new UnknownIdentifierTypeException($type.' is an unknown identifier type');
    }

    /**
     * Log request statistics.
     *
     * @param string $type
     *   The identifier type (e.g. 'pid', 'isbn', etc).
     * @param array $identifiers
     *   Array of identifiers of {type}
     * @param array $results
     *   An array of result from an Elastica search
     * @param Request $request
     *   The Symfony HTTP Request
     */
    protected function logStatistics(string $type, array $identifiers, array $results, Request $request): void
    {
        $className = substr(\get_class($this), strrpos(\get_class($this), '\\') + 1);

        $imageUrls = $this->getImageUrls($results);

        $this->statsLoggingService->info('Cover request/response', [
            'service' => $className,
            'clientID' => 'REST_API',
            'remoteIP' => $request->getClientIp(),
            'isType' => $type,
            'isIdentifiers' => $identifiers,
            'fileNames' => array_values($imageUrls),
            'matches' => $this->getMatches($imageUrls, $identifiers, $type),
        ]);

        $this->metricsService->counter('api_request_total', 'Total number of requests', 1, ['type' => 'rest']);
    }

    /**
     * Create array of matches between searches and found image urls.
     *
     * @param array $imageUrls
     *   Array of found image urls
     * @param array $identifiers
     *   Array of requested identifiers
     * @param string $identifierType
     *   Type of the identifiers
     *
     * @return array
     *   Array of matches between found imageUrls and requested identifiers
     */
    private function getMatches(array $imageUrls, array $identifiers, string $identifierType): array
    {
        $matches = [];

        foreach ($identifiers as $identifier) {
            $match = [
                'match' => $imageUrls[$identifier] ?? null,
                'identifier' => $identifier,
                'type' => $identifierType,
            ];

            $matches[] = $match;
        }

        return $matches;
    }
}
