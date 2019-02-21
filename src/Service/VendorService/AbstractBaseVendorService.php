<?php
/**
 * @file
 * Service for updating data from 'Bogportalen'.
 */

namespace App\Service\VendorService;

use App\Entity\Source;
use App\Entity\Vendor;
use App\Event\VendorEvent;
use App\Exception\IllegalVendorServiceException;
use App\Exception\UnknownVendorServiceException;
use App\Utils\Message\VendorImportResultMessage;
use App\Utils\Types\IdentifierType;
use App\Utils\Types\VendorState;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Lock\Factory;
use Symfony\Component\Lock\Store\SemaphoreStore;

abstract class AbstractBaseVendorService
{
    protected const VENDOR_ID = 0;

    protected const BATCH_SIZE = 200;
    protected const ERROR_RUNNING = 'Import already running';

    private $vendor;

    protected $em;
    protected $dispatcher;
    protected $statsLogger;

    protected $queue = true;
    protected $totalUpdated = 0;
    protected $totalInserted = 0;
    protected $totalDeleted = 0;
    protected $totalIsIdentifiers = 0;

    /**
     * AbstractBaseVendorService constructor.
     *
     * @param eventDispatcherInterface $eventDispatcher
     *   Dispatcher to trigger async jobs on import
     * @param entityManagerInterface $entityManager
     *   Doctrine entity manager
     * @param loggerInterface $statsLogger
     *   Logger object to send stats to ES
     */
    public function __construct(EventDispatcherInterface $eventDispatcher, EntityManagerInterface $entityManager,
                                LoggerInterface $statsLogger)
    {
        $this->em = $entityManager;
        $this->dispatcher = $eventDispatcher;
        $this->statsLogger = $statsLogger;
    }

    /**
     * Load new data from vendor.
     *
     * @param bool $queue
     *   If FALSE the queue system will not be activate and images will not be
     *   downloaded to cover store
     * @param int $limit
     *   Set a limit to the amount of records to import
     *
     * @return VendorImportResultMessage
     */
    abstract public function load(bool $queue = true, int $limit = null): VendorImportResultMessage;

    /**
     * Get the database id of the vendor the class represents.
     *
     * @return int
     *
     * @throws IllegalVendorServiceException
     */
    final public function getVendorId(): int
    {
        if (!is_int($this::VENDOR_ID) || $this::VENDOR_ID <= 0) {
            throw new IllegalVendorServiceException('VENDOR_ID must be a positive non-zero integer. Illegal value detected.');
        }

        return $this::VENDOR_ID;
    }

    /**
     * Get the name of the vendor.
     *
     * @return string
     *
     * @throws UnknownVendorServiceException
     * @throws IllegalVendorServiceException
     */
    final public function getVendorName(): string
    {
        return $this->getVendor()->getName();
    }

    /**
     * Get the Vendor object.
     *
     * @return Vendor
     *
     * @throws UnknownVendorServiceException
     * @throws IllegalVendorServiceException
     */
    final public function getVendor(): Vendor
    {
        // If a subclass has cleared all from the entity manager we reload the
        // vendor from the DB.
        if (!$this->vendor || !$this->em->contains($this->vendor)) {
            $vendorRepos = $this->em->getRepository(Vendor::class);
            $this->vendor = $vendorRepos->findOneById($this->getVendorId());
        }

        if (!$this->vendor || empty($this->vendor)) {
            throw new UnknownVendorServiceException('Vendor with ID: '.$this->getVendorId().' not found in DB');
        }

        return $this->vendor;
    }

    /**
     * Acquire service lock to ensure we don't run multiple imports for the
     * same vendor in parallel.
     *
     * @return bool
     *
     * @throws IllegalVendorServiceException
     */
    protected function acquireLock(): bool
    {
        $store = new SemaphoreStore();
        $factory = new Factory($store);

        $lock = $factory->createLock('app-vendor-service-load-'.$this->getVendorId());

        return $lock->acquire();
    }

    /**
     * Update or insert source materials.
     *
     * @param array $identifierImageUrlArray
     *   Array with identifier numbers => image URLs as key/value to update or insert
     * @param string $identifierType
     *   The type of identifier
     * @param int $batchSize
     *   The number of records to flush to the database pr. batch.
     *
     * @throws \Exception
     */
    protected function updateOrInsertMaterials(array &$identifierImageUrlArray, string $identifierType = IdentifierType::ISBN, int $batchSize = self::BATCH_SIZE): void
    {
        $sourceRepos = $this->em->getRepository(Source::class);

        $offset = 0;
        $count = \count($identifierImageUrlArray);

        while ($offset < $count) {
            // Update or insert in batches. Because doctrine lacks
            // 'INSERT ON DUPLICATE KEY UPDATE' we need to search for and load
            // sources already in the db.

            // @TODO this can be solved by using $entityManager->merge($entity) but
            // this requires that we can predict the ID of the entity
            // Doctrine allows for composite primary keys so we can use
            // (isbn, vendor) but this requires a custom DataProvider in
            // API-platform.
            $batch = \array_slice($identifierImageUrlArray, $offset, self::BATCH_SIZE, true);

            // Split into to results arrays (updated and inserted).
            $updatedIds = [];
            $insertedIds = [];

            // Load batch from database to enable updates.
            $sources = $sourceRepos->findByMatchIdList($identifierType, $batch, $this->getVendor());

            foreach ($batch as $identifier => $imageUrl) {
                if (array_key_exists($identifier, $sources)) {
                    $source = $sources[$identifier];
                    ++$this->totalUpdated;
                    $updatedIds[] = $identifier;
                } else {
                    $source = new Source();
                    $this->em->persist($source);
                    ++$this->totalInserted;
                    $insertedIds[] = $identifier;
                }

                $source->setMatchType($identifierType)
                    ->setMatchId($identifier)
                    ->setVendor($this->vendor)
                    ->setDate(new \DateTime())
                    ->setOriginalFile($imageUrl);

                ++$this->totalIsIdentifiers;
            }

            $this->em->flush();
            $this->em->clear('App\Entity\Source');

            // Send event with the last batch to the job processors.
            if ($this->queue) {
                if (!empty($insertedIds)) {
                    $event = new VendorEvent(VendorState::INSERT, $insertedIds, $identifierType, $this->vendor->getId());
                    $this->dispatcher->dispatch($event::NAME, $event);
                }
                if (!empty($updatedIds)) {
                    $event = new VendorEvent(VendorState::UPDATE, $updatedIds, $identifierType, $this->vendor->getId());
                    $this->dispatcher->dispatch($event::NAME, $event);
                }

                // @TODO: DELETED event???
            }

            $offset += $batchSize;
        }
    }

    /**
     * Delete all Source materials not found in latest import.
     *
     * @param array $identifierArray
     *   Array of found identification numbers
     *
     * @return int
     *   The number of source materials deleted
     */
    protected function deleteRemovedMaterials(array &$identifierArray): int
    {
        // @TODO implement queueing jobs for DeleteProcessor
    }

    /**
     * Log statistics.
     */
    protected function logStatistics(): void
    {
        $className = substr(\get_class($this), strrpos(\get_class($this), '\\') + 1);

        // Stats logger.
        $this->statsLogger->info($this->getVendorName() . ' records read', [
            'service' => $className,
            'records' => $this->totalIsIdentifiers,
            'updated' => $this->totalUpdated,
            'inserted' => $this->totalInserted,
            'deleted' => $this->totalDeleted,
        ]);
    }
}
