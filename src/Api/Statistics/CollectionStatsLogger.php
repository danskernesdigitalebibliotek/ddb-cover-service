<?php
/**
 * @file
 * Collection Statitics Logger
 */

namespace App\Api\Statistics;

use App\Service\StatsLoggingService;
use ItkDev\MetricsBundle\Service\MetricsService;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;

/**
 * Class CollectionStatsLogger.
 */
class CollectionStatsLogger
{
    private StatsLoggingService $statsLoggingService;
    private MetricsService $metricsService;
    private RequestStack $requestStack;
    private Security $security;

    private const SERVICE = 'CoverCollectionDataProvider';
    private const MESSAGE = 'Cover request/response';

    /**
     * CollectionStatsLogger constructor.
     */
    public function __construct(StatsLoggingService $statsLoggingService, MetricsService $metricsService, RequestStack $requestStack, Security $security)
    {
        $this->statsLoggingService = $statsLoggingService;
        $this->metricsService = $metricsService;
        $this->requestStack = $requestStack;
        $this->security = $security;
    }

    /**
     * Log metrics and statistics for the request.
     */
    public function logRequest(string $type, array $identifiers, array $results): void
    {
        $this->logStatistics($type, $identifiers, $results);
        $this->logMetrics();
    }

    /**
     * Log request statistics.
     *
     * @param string $type        The identifier type (e.g. 'pid', 'isbn', etc).
     * @param array  $identifiers Array of identifiers of {type}
     * @param array  $results     An array of result from an Elastica search
     */
    private function logStatistics(string $type, array $identifiers, array $results): void
    {
        $imageUrls = $this->getImageUrls($results);
        $clientIp = $this->requestStack->getCurrentRequest()->getClientIp();

        $this->statsLoggingService->info(self::MESSAGE, [
            'service' => self::SERVICE,
            'clientID' => $this->security->getUser()->getAgency(),
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
        $this->metricsService->counter('api_request_total', 'Total number of requests', 1, ['type' => 'rest']);
    }

    /**
     * Get image URLs from search result.
     *
     * @param array $results An array of result from an Elastica search
     *
     * @return array An array of image urls strings from the results
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
     * @param array  $imageUrls      Array of found image urls
     * @param array  $identifiers    Array of requested identifiers
     * @param string $identifierType Type of the identifiers
     *
     * @return array Array of matches between found imageUrls and requested identifiers
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
