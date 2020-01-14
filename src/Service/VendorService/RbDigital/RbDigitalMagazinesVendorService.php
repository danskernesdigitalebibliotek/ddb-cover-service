<?php
/**
 * @file
 * Service for updating book covers from 'RB Digital'.
 */

namespace App\Service\VendorService\RbDigital;

use App\Service\DataWell\SearchService;
use App\Service\VendorService\AbstractBaseVendorService;
use App\Service\VendorService\ProgressBarTrait;
use App\Service\VendorService\RbDigital\DataConverter\RbDigitalMagazinesPublicUrlConverter;
use App\Utils\Message\VendorImportResultMessage;
use App\Utils\Types\IdentifierType;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class RbDigitalBooksVendorService.
 */
class RbDigitalMagazinesVendorService extends AbstractBaseVendorService
{
    use ProgressBarTrait;

    protected const VENDOR_ID = 8;

    private const VENDOR_SEARCH_TERM = 'facet.acSource="rbdigital magazines"';
    private const VENDOR_IMAGE_URL_BASE = 'http://www.zinio.com/img';

    private $searchService;
    private $httpClient;

    /**
     * RbDigitalMagazinesVendorService constructor.
     *
     * @param EventDispatcherInterface $eventDispatcher
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface $statsLogger
     * @param SearchService $searchService
     * @param \GuzzleHttp\ClientInterface $httpClient
     */
    public function __construct(EventDispatcherInterface $eventDispatcher, EntityManagerInterface $entityManager, LoggerInterface $statsLogger, SearchService $searchService, ClientInterface $httpClient)
    {
        parent::__construct($eventDispatcher, $entityManager, $statsLogger);

        $this->searchService = $searchService;
        $this->httpClient = $httpClient;
    }

    /**
     * {@inheritdoc}
     */
    public function load(bool $queue = true, int $limit = null): VendorImportResultMessage
    {
        if (!$this->acquireLock()) {
            return VendorImportResultMessage::error(parent::ERROR_RUNNING);
        }

        $this->queue = $queue;
        $this->progressStart('Search data well for: "'.self::VENDOR_SEARCH_TERM.'"');

        $offset = 1;
        try {
            do {
                $this->progressMessage('Search data well for: "'.self::VENDOR_SEARCH_TERM.'" (Offset: '.$offset.')');

                // Search the data well for material with acSource set to "rbdigital magazines".
                [$pidArray, $more, $offset] = $this->searchService->search(self::VENDOR_SEARCH_TERM, $offset);

                // Get the data wells image urls from the results
                $pidArray = $this->getImageUrls($pidArray);

                // Get the redirect target for the image urls
                $pidArray = $this->getRedirectedUls($pidArray, $offset - SearchService::SEARCH_LIMIT);

                // Remove 'format' parameter from image urls
                RbDigitalMagazinesPublicUrlConverter::convertArrayValues($pidArray);

                $batchSize = \count($pidArray);
                $this->updateOrInsertMaterials($pidArray, IdentifierType::PID, $batchSize);

                $this->progressMessageFormatted($this->totalUpdated, $this->totalInserted, $this->totalIsIdentifiers);
                $this->progressAdvance();

                if ($limit && $this->totalIsIdentifiers >= $limit) {
                    $more = false;
                }
            } while ($more);

            return VendorImportResultMessage::success($this->totalIsIdentifiers, $this->totalUpdated, $this->totalInserted, $this->totalDeleted);
        } catch (\Exception $exception) {
            return VendorImportResultMessage::error($exception->getMessage());
        }
    }

    /**
     * Get the image urls for all results in array.
     *
     * @param array $pidArray
     *   An array of pid => result to be converted
     *
     * @return array
     */
    private function getImageUrls(array $pidArray): array
    {
        $result = [];
        foreach ($pidArray as $pid => $item) {
            $result[$pid] = $this->getImageUrl($item['record']['identifier']);
        }

        return $result;
    }

    /**
     * Get the image urls from the data well result.
     *
     * @param array $result
     *   A data well result array
     *
     * @return string|null
     */
    private function getImageUrl(array $result): ?string
    {
        foreach ($result as $item) {
            $pos = strpos($item['$'], self::VENDOR_IMAGE_URL_BASE);
            if (false !== $pos) {
                return $item['$'];
            }
        }

        return null;
    }

    /**
     * Get the redirect target for all urls.
     *
     * @param array $pidArray
     *   An array of pid => urls to be converted
     * @param int $offset
     *   The current search offset
     *
     * @return array
     *   The resulted array of key => urls
     *
     * @throws GuzzleException
     */
    private function getRedirectedUls(array $pidArray, int $offset): array
    {
        $result = [];
        foreach ($pidArray as $pid => $url) {
            $this->progressMessage('Get the redirect target for image url #'.$offset);

            try {
                $response = $this->httpClient->request('HEAD', $url, [
                    'allow_redirects' => [
                        'max' => 10,
                        'track_redirects' => true,
                    ],
                ]);
                if (200 === $response->getStatusCode()) {
                    $headersRedirect = $response->getHeader(\GuzzleHttp\RedirectMiddleware::HISTORY_HEADER);
                    $result[$pid] = array_pop($headersRedirect);
                }
            } catch (\Exception $exception) {
                // Ignore
            }

            $this->progressAdvance();
            ++$offset;
        }

        return $result;
    }
}
