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
use App\Event\SearchNoHitEvent;
use App\Service\MetricsService;
use App\Utils\Types\IdentifierType;
use App\Utils\Types\NoHitItem;
use Elastica\Query;
use Elastica\Type;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class AbstractElasticSearchDataProvider.
 */
abstract class AbstractElasticSearchDataProvider
{
    protected $index;
    protected $statsLogger;
    protected $metricsService;
    protected $requestStack;
    protected $dispatcher;
    protected $factory;

    /**
     * SearchCollectionDataProvider constructor.
     *
     * @param Type $index
     *   Elastica index search type
     * @param RequestStack $requestStack
     *   Symfony request stack
     * @param LoggerInterface $statsLogger
     *   Logger for statistics
     * @param MetricsService $metricsService
     *   Log metric information.
     * @param EventDispatcherInterface $dispatcher
     *   Symfony Event Dispatcher
     * @param IdentifierFactory $factory
     *   Factory to create Identifier Data Transfer Objects (DTOs)
     */
    public function __construct(Type $index, RequestStack $requestStack, LoggerInterface $statsLogger,
                                MetricsService $metricsService, EventDispatcherInterface $dispatcher, IdentifierFactory $factory)
    {
        $this->index = $index;
        $this->requestStack = $requestStack;
        $this->statsLogger = $statsLogger;
        $this->metricsService = $metricsService;
        $this->dispatcher = $dispatcher;
        $this->factory = $factory;
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
            $this->metricsService->counter('no_hits_total', 'Total number of no-hits', count($noHits), ['type' => 'rest']);

            // Defer no hit processing to the kernel terminate event after
            // response has been delivered.
            $this->dispatcher->addListener(
                KernelEvents::TERMINATE,
                function (TerminateEvent $event) use ($noHits) {
                    $noHitEvent = new SearchNoHitEvent($noHits);
                    $this->dispatcher->dispatch($noHitEvent::NAME, $noHitEvent);
                }
            );
        }
    }

    /**
     * Build Elastic query from IS types and IS identifiers.
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
        $boolQuery = new Query\BoolQuery();

        foreach ($identifiers as $identifier) {
            $identifierFieldTermQuery = new Query\Term();
            $identifierFieldTermQuery->setTerm('isIdentifier', $identifier);
            $boolQuery->addShould($identifierFieldTermQuery);
        }

        $query = new Query();
        $query->setQuery($boolQuery);
        $query->setSize(count($identifiers));

        return $query;
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
     * @return mixed
     *   An array of image urls strings from the results
     */
    protected function getImageUrls(array $results)
    {
        $urls = [];
        foreach ($results as $result) {
            $urls[] = $result['imageUrl'];
        }

        return empty($urls) ? null : $urls;
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
        $this->statsLogger->info('Cover request/response', [
            'service' => $className,
            // @TODO Log clientID when authentication implemented, log 'REST_API' for now to allow stats filtering on REST.
            'clientID' => 'REST_API',
            'remoteIP' => $request->getClientIp(),
            'isType' => $type,
            'isIdentifiers' => $identifiers,
            'fileNames' => $this->getImageUrls($results),
        ]);

        $this->metricsService->counter('api_request_total', 'Total number of requests', 1, ['type' => 'rest']);
    }
}
