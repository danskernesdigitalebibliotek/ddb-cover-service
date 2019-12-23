<?php

/**
 * @file
 * Handle search at open platform.
 */

namespace App\Service\OpenPlatform;

use App\Exception\PlatformSearchException;
use App\Utils\OpenPlatform\Material;
use App\Utils\Types\IdentifierType;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Class SearchService.
 */
class SearchService
{
    private $params;
    private $cache;
    private $statsLogger;
    private $authenticationService;
    private $client;

    const SEARCH_LIMIT = 50;

    private $fields = [
        'title',
        'creator',
        'date',
        'publisher',
        'pid',
        'identifierISBN',
        'identifierISSN',
        'identifierISMN',
        'identifierISRC',
    ];

    private $searchCacheTTL;
    private $searchURL;
    private $searchIndex;

    /**
     * SearchService constructor.
     *
     * @param parameterBagInterface $params
     *   Access to environment variables
     * @param adapterInterface $cache
     *   Cache object to store results
     * @param loggerInterface $statsLogger
     *   Logger object to send stats to ES
     * @param authenticationService $authenticationService
     *   The Open Platform authentication service
     * @param ClientInterface $httpClient
     *   Guzzle Client
     */
    public function __construct(ParameterBagInterface $params, AdapterInterface $cache,
                                LoggerInterface $statsLogger, AuthenticationService $authenticationService,
                                ClientInterface $httpClient)
    {
        $this->params = $params;
        $this->cache = $cache;
        $this->statsLogger = $statsLogger;
        $this->authenticationService = $authenticationService;
        $this->client = $httpClient;

        $this->searchURL = $this->params->get('openPlatform.search.url');
        $this->searchIndex = $this->params->get('openPlatform.search.index');
        $this->searchCacheTTL = (int) $this->params->get('openPlatform.search.ttl');
    }

    /**
     * Search the data well through the open platform.
     *
     * Note: that cache is utilized, hence the result may not be fresh.
     *
     * @param $identifier
     *   The identifier to search for
     * @param string $type
     *   The type of identifier
     * @param bool $refresh
     *   If set to TRUE the cache is by-passed. Default: FALSE.
     *
     * @return material
     *   Material object with the result
     *
     * @throws PlatformSearchException
     * @throws \App\Exception\MaterialTypeException
     * @throws \App\Exception\PlatformAuthException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function search($identifier, $type, $refresh = false): Material
    {
        // Try getting item from cache.
        $item = $this->cache->getItem('openplatform.search_query'.str_replace(':', '', $identifier));

        // We return the material object and not the $item->get() as that
        // prevents proper testing of the service.
        $material = null;

        // Check if the access token is located in local file cache to speed up the
        // process.
        if ($refresh || !$item->isHit()) {
            try {
                $res = $this->recursiveSearch($identifier, $type);
            } catch (GuzzleException $exception) {
                throw new PlatformSearchException($exception->getMessage(), $exception->getCode());
            }

            // Handle zero hit.
            if (empty($res)) {
                // Simply create an empty material object. Which then can be tested with
                // the isEmpty() method.
                $material = new Material();
            } else {
                $material = $this->parseResult($res);

                // Check that the search IS is part of the parsed result. As this is not
                // always the case. e.g. 9788798970804
                if (!$material->hasIdentifier($type, $identifier)) {
                    $material->addIdentifier($type, $identifier);
                }
            }

            $item->expiresAfter($this->searchCacheTTL);
            $item->set($material);
            $this->cache->save($item);

            $this->statsLogger->info('Search requested', [
                'service' => 'SearchService',
                'cache' => false,
                'id' => $identifier,
                'no hits' => $material->isEmpty(),
            ]);
        } else {
            $this->statsLogger->info('Search requested', [
                'service' => 'SearchService',
                'cache' => true,
                'id' => $identifier,
                'no hits' => $item->get()->isEmpty(),
            ]);
            $material = $item->get();
        }

        return $material;
    }

    /**
     * Parse the search result from the date well.
     *
     * @param array $result
     *   The results from the data well
     *
     * @return material
     *   Material with all the information collected
     *
     * @throws \App\Exception\MaterialTypeException
     */
    private function parseResult(array $result)
    {
        $material = new Material();
        foreach ($result as $key => $items) {
            switch ($key) {
                case 'pid':
                  $material->addIdentifier(IdentifierType::PID, reset($items));

                  // We know that the last part of the PID is the material faust
                  // so we extract that here and add that as a identifier as
                  // well.
                  if (preg_match('/:(1?\d{8}$)/', reset($items), $matches)) {
                      $material->addIdentifier(IdentifierType::FAUST, $matches[1]);
                  }
                  break;

                case 'identifierISBN':
                    foreach ($items as $item) {
                        $material->addIdentifier(IdentifierType::ISBN, $this->stripDashes($item));
                    }
                  break;

                case 'identifierISSN':
                    foreach ($items as $item) {
                        $material->addIdentifier(IdentifierType::ISSN, $this->stripDashes($item));
                    }
                  break;

                case 'identifierISMN':
                    foreach ($items as $item) {
                        $material->addIdentifier(IdentifierType::ISMN, $this->stripDashes($item));
                    }
                  break;

                case 'identifierISR':
                    foreach ($items as $item) {
                        $material->addIdentifier(IdentifierType::ISRC, $this->stripDashes($item));
                    }
                break;

                default:
                    $method = 'set'.ucfirst($key);
                    call_user_func([$material, $method], reset($items));
                    break;
            }
        }

        return $material;
    }

    /**
     * Strip dashes from string.
     *
     * @param string $str
     *   The string to strip
     *
     * @return string
     *   The striped string
     */
    private function stripDashes($str)
    {
        return str_replace('-', '', $str);
    }

    /**
     * Recursive search until no more results exists for the query.
     *
     * This is need as the open platform allows an max limit of 50 elements, so
     * if more results exists this calls it self to get all results.
     *
     * @param string $identifier
     *   The identifier to search for
     * @param string $type
     *   The identifier type.
     * @param int $offset
     *   The offset to start getting results
     * @param array $results
     *   The current results array
     *
     * @return array
     *   The results currently found. If recursion is completed all the results.
     *
     * @throws GuzzleException
     * @throws \App\Exception\PlatformAuthException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function recursiveSearch(string $identifier, string $type, int $offset = 0, array $results = []): array
    {
        $token = $this->authenticationService->getAccessToken();
        $query = $this->searchIndex.'='.$identifier;
        if ($type == IdentifierType::PID) {
            // If this is a search after a pid simply search for it and not in the search index.
            $query = $identifier;
        }
        $response = $this->client->request('POST', $this->searchURL, [
            RequestOptions::JSON => [
                'fields' => $this->fields,
                'access_token' => $token,
                'pretty' => false,
                'timings' => false,
                'q' => $query,
                'offset' => $offset,
                'limit' => $this::SEARCH_LIMIT,
            ],
        ]);

        $content = $response->getBody()->getContents();
        $json = json_decode($content, true);

        if (isset($json['hitCount']) && $json['hitCount'] > 0) {
            $results = array_merge($results, reset($json['data']));
        }

        // If there are more results get the next chunk.
        if (isset($json['hitCount']) && false !== $json['more']) {
            $this->recursiveSearch($identifier, $type, $offset + $this::SEARCH_LIMIT, $results);
        }

        return $results;
    }
}
