<?php

/**
 * @file
 * Use a library's data well access to get comic+ covers.
 */

namespace App\Service\VendorService\TheMovieDatabase;

use App\Exception\IllegalVendorServiceException;
use App\Exception\UnknownVendorServiceException;
use App\Service\VendorService\AbstractBaseVendorService;
use App\Service\VendorService\ProgressBarTrait;
use App\Service\VendorService\TheMovieDatabase\Event\ResultEvent;
use App\Utils\Message\VendorImportResultMessage;
use App\Utils\Types\IdentifierType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class DataWellVendorService.
 */
class TheMovieDatabaseVendorService extends AbstractBaseVendorService
{
    use ProgressBarTrait;

    protected const VENDOR_ID = 6;

    private $dataWell;
    private $api;

    /**
     * DataWellVendorService constructor.
     *
     * @param EventDispatcherInterface $eventDispatcher
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface $statsLogger
     * @param TheMovieDatabaseSearchService $dataWell
     */
    public function __construct(EventDispatcherInterface $eventDispatcher, EntityManagerInterface $entityManager,
                              LoggerInterface $statsLogger, TheMovieDatabaseSearchService $dataWell, TheMovieDatabaseApiService $api)
    {
        parent::__construct($eventDispatcher, $entityManager, $statsLogger);

        $this->dataWell = $dataWell;
        $this->api = $api;
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
        $this->progressStart('Search data well for movies');

        $offset = 1;
        try {
            do {
                // Search the data well for materials.
                // @TODO We need multiple queries t find all movies so this needs to ba a list of queries we iterate over (Maybe as class constant).
                $query = 'phrase.type="blu-ray"';
                [$resultArray, $more, $offset] = $this->dataWell->search($query, $offset);

                $pidArray = array_map(function($value) { return ''; }, $resultArray);

                $batchSize = \count($pidArray);
                $this->updateOrInsertMaterials($pidArray, IdentifierType::PID, $batchSize);

                $event = new ResultEvent($resultArray, IdentifierType::PID, $this->getVendorId());
                $this->dispatcher->dispatch($event::NAME, $event);

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
        $this->dataWell->setSearchUrl($this->getVendor()->getDataServerURI());
        $this->dataWell->setUser($this->getVendor()->getDataServerUser());
        $this->dataWell->setPassword($this->getVendor()->getDataServerPassword());
    }
}
