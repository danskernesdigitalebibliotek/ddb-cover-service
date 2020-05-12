<?php

namespace App\Api\Statistics;

use App\Service\MetricsService;
use App\Service\StatsLoggingService;
use Symfony\Component\HttpFoundation\RequestStack;

class CollectionStatsLogger
{
    private $statsLoggingService;
    private $metricsService;
    private $requestStack;

    private const SERVICE = 'CoverCollectionDataProvider';
    private const CLIENT_ID = 'REST_API';
    private const MESSAGE = 'Cover request/response';

    /**
     * CollectionStatsLogger constructor.
     *
     * @param StatsLoggingService $statsLoggingService
     * @param MetricsService $metricsService
     * @param RequestStack $requestStack
     */
    public function __construct(StatsLoggingService $statsLoggingService, MetricsService $metricsService, RequestStack $requestStack)
    {
        $this->statsLoggingService = $statsLoggingService;
        $this->metricsService = $metricsService;
        $this->requestStack = $requestStack;
    }

    /**
     * Log metrics and statistics for the request.
     *
     * @param string $type
     * @param array $identifiers
     * @param array $results
     */
    public function logRequest(string $type, array $identifiers, array $results): void
    {
        $this->logStatistics($type, $identifiers, $results);
        $this->logMetrics();
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
     */
    private function logStatistics(string $type, array $identifiers, array $results): void
    {
        $imageUrls = $this->getImageUrls($results);
        $clientIp = $this->requestStack->getCurrentRequest()->getClientIp();

        $this->statsLoggingService->info(self::MESSAGE, [
            'service' => self::SERVICE,
            'clientID' => self::CLIENT_ID,
            'remoteIP' => $clientIp,
            'isType' => $type,
            'isIdentifiers' => $identifiers,
            'fileNames' => array_values($imageUrls),
            'matches' => $this->getMatches($imageUrls, $identifiers, $type),
        ]);
    }

    /**
     * Log metrics for request.
     */
    private function logMetrics(): void
    {
        $this->metricsService->counter('rest_requests_total', 'Total rest requests', 1, ['type' => 'collection']);
        $this->metricsService->counter('api_request_total', 'Total number of requests', 1, ['type' => 'rest']);
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
    private function getImageUrls(array $results): array
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
