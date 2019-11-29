<?php

/**
 * @file
 * Use a library's data well access to get comic+ covers.
 */

namespace App\Service\VendorService\DataWell;

use App\Exception\IllegalVendorServiceException;
use App\Exception\UnknownVendorServiceException;
use App\Service\VendorService\AbstractBaseVendorService;
use App\Service\VendorService\DataWell\DataConverter\IversePublicUrlConverter;
use App\Service\VendorService\ProgressBarTrait;
use App\Utils\Message\VendorImportResultMessage;
use App\Utils\Types\IdentifierType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class DataWellVendorService.
 */
class DataWellVendorService extends AbstractBaseVendorService
{
    use ProgressBarTrait;

    protected const VENDOR_ID = 4;
    private const VENDOR_ARCHIVE_NAME = 'comics+';

    private $datawell;

    /**
     * DataWellVendorService constructor.
     *
     * @param EventDispatcherInterface $eventDispatcher
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface $statsLogger
     * @param DataWellSearchService $datawell
     */
    public function __construct(EventDispatcherInterface $eventDispatcher, EntityManagerInterface $entityManager,
                              LoggerInterface $statsLogger, DataWellSearchService $datawell)
    {
        parent::__construct($eventDispatcher, $entityManager, $statsLogger);

        $this->datawell = $datawell;
    }

    /**
     * @{@inheritdoc}
     */
    public function load(bool $queue = true, int $limit = null): VendorImportResultMessage
    {
        if (!$this->acquireLock()) {
            return VendorImportResultMessage::error(parent::ERROR_RUNNING);
        }

        // We're lazy loading the config to avoid errors from missing config values on dependency injection
        $this->loadConfig();

        $this->queue = $queue;
        $this->progressStart('Search data well for: "'.self::VENDOR_ARCHIVE_NAME.'"');

        $offset = 1;
        try {
            do {
                // Search the data well for material with acSource set to "comics plus".
                [$pidArray, $more, $offset] = $this->datawell->search('comics plus', $offset);

                // Convert images url from 'medium' to 'large'
                IversePublicUrlConverter::convertArrayValues($pidArray);

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
     * Set config fro service from DB vendor object.
     *
     * @throws UnknownVendorServiceException
     * @throws IllegalVendorServiceException
     */
    private function loadConfig(): void
    {
        // Set the service access configuration from the vendor.
        $this->datawell->setSearchUrl($this->getVendor()->getDataServerURI());
        $this->datawell->setUser($this->getVendor()->getDataServerUser());
        $this->datawell->setPassword($this->getVendor()->getDataServerPassword());
    }
}
