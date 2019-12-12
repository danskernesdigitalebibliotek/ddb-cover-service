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
use App\Utils\Types\IdentifierType;
use App\Utils\Types\NoHitItem;
use Elastica\Query;
use Elastica\Type;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class AbstractElasticSearchDataProvider.
 */
abstract class AbstractElasticSearchDataProvider
{
    protected $index;
    protected $statsLogger;
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
     * @param EventDispatcherInterface $dispatcher
     *   Symfony Event Dispatcher
     * @param IdentifierFactory $factory
     *   Factory to create Identifier Data Transfer Objects (DTOs)
     */
    public function __construct(Type $index, RequestStack $requestStack, LoggerInterface $statsLogger, EventDispatcherInterface $dispatcher, IdentifierFactory $factory)
    {
        $this->index = $index;
        $this->requestStack = $requestStack;
        $this->statsLogger = $statsLogger;
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

        if ($noHits) {
            $event = new SearchNoHitEvent($noHits);
            $this->dispatcher->dispatch($event::NAME, $event);
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

        $identifierFieldTermsQuery = new Query\Terms();
        $identifierFieldTermsQuery->setTerms('isIdentifier', $identifiers);
        $boolQuery->addMust($identifierFieldTermsQuery);

        $typeFieldTermQuery = new Query\Terms();
        $typeFieldTermQuery->setTerms('isType', [$type]);
        $boolQuery->addMust($typeFieldTermQuery);

        $query = new Query();
        $query->addSort(['isIdentifier' => ['order' => 'asc']]);
        $query->setQuery($boolQuery);

        return $query;
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
            $data = $result->getData();
            $urls[] = $data['imageUrl'];
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
    }
}
