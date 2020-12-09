<?php
/**
 * @file
 * Collection Statitics Logger
 */

namespace App\Api\Statistics;

use App\Message\FaktorMessage;
use App\Service\MetricsService;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Security;

/**
 * Class CollectionStatsLogger.
 */
class CollectionStatsLogger
{
    private $statsLoggingService;
    private $metricsService;
    private $requestStack;
    private $security;
    private $bus;
    private $traceId;

    /**
     * CollectionStatsLogger constructor.
     *
     * @param MetricsService $metricsService
     * @param RequestStack $requestStack
     * @param Security $security
     * @param MessageBusInterface $bus
     */
    public function __construct(string $bindTraceId, MetricsService $metricsService, RequestStack $requestStack, Security $security, MessageBusInterface $bus)
    {
        $this->traceId = $bindTraceId;
        $this->metricsService = $metricsService;
        $this->requestStack = $requestStack;
        $this->security = $security;
        $this->bus = $bus;
    }

    /**
     * Log metrics and statistics for the request.
     *
     * @param string $type
     *   The identifier type (e.g. 'pid', 'isbn', etc).
     * @param array $identifiers
     *   Array of identifiers of {type}
     * @param array $results
     *   An array of result from an Elastica search
     */
    public function logRequest(string $type, array $identifiers, array $results): void
    {
        $this->sendStatistics($type, $identifiers, $results);
        $this->logMetrics();
    }

    /**
     * Send statistics message to faktor.
     *
     * @param string $type
     *   The identifier type (e.g. 'pid', 'isbn', etc).
     * @param array $identifiers
     *   Array of identifiers of {type}
     * @param array $results
     *   An array of result from an Elastica search
     */
    private function sendStatistics(string $type, array $identifiers, array $results): void
    {
        $imageUrls = $this->getImageUrls($results);
        $clientIp = $this->requestStack->getCurrentRequest()->getClientIp();

        $message = new FaktorMessage();
        $message->setClientID($this->security->getUser()->getAgency())
            ->setRemoteIP($clientIp)
            ->setIsType($type)
            ->setIsIdentifiers($identifiers)
            ->setFileNames(array_values($imageUrls))
            ->setMatches($this->getMatches($imageUrls, $identifiers, $type))
            ->setTraceId($this->traceId);

        $this->bus->dispatch($message);
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
