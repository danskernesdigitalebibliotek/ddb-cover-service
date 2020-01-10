<?php
/**
 * @file
 * Service for updating book covers from 'RB Digital'.
 */

namespace App\Service\VendorService\RbDigital;

use App\Exception\IllegalVendorServiceException;
use App\Exception\UnknownVendorServiceException;
use App\Service\VendorService\AbstractBaseVendorService;
use App\Service\VendorService\ProgressBarTrait;
use App\Service\VendorService\RbDigital\DataConverter\RbDigitalPublicUrlConverter;
use App\Utils\Message\VendorImportResultMessage;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Scriptotek\Marc\Collection;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class RbDigitalBooksVendorService.
 */
class RbDigitalBooksVendorService extends AbstractBaseVendorService
{
    use ProgressBarTrait;

    protected const VENDOR_ID = 7;
    protected const BATCH_SIZE = 10;

    // List of directories with book records
    private const VENDOR_ARCHIVES_DIRECTORIES = [
        'Recorded Books eAudio World-Wide Library Subscription',
        'Recorded Books eBook Classics Collection',
    ];

    private $local;
    private $ftp;
    private $cache;

    /**
     * RbDigitalVendorService constructor.
     *
     * @param eventDispatcherInterface $eventDispatcher
     *   Dispatcher to trigger async jobs on import
     * @param Filesystem $local
     *   Flysystem adapter for local filesystem
     * @param Filesystem $ftp
     *   Flysystem adapter for remote ftp server
     * @param EntityManagerInterface $entityManager
     *   Doctrine entity manager
     * @param LoggerInterface $statsLogger
     *   Logger object to send stats to ES
     * @param AdapterInterface $cache
     *   Cache adapter for the application
     */
    public function __construct(EventDispatcherInterface $eventDispatcher, Filesystem $local, Filesystem $ftp, EntityManagerInterface $entityManager, LoggerInterface $statsLogger, AdapterInterface $cache)
    {
        parent::__construct($eventDispatcher, $entityManager, $statsLogger);

        $this->local = $local;
        $this->ftp = $ftp;
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function load(bool $queue = true, int $limit = null): VendorImportResultMessage
    {
        if (!$this->acquireLock()) {
            return VendorImportResultMessage::error(parent::ERROR_RUNNING);
        }

        // We're lazy loading the config to avoid errors from missing config values on dependency injection
        $this->loadConfig();

        $mrcFileNames = [];
        foreach (self::VENDOR_ARCHIVES_DIRECTORIES as $directory) {
            foreach ($this->ftp->listContents($directory) as $content) {
                $mrcFileNames[] = $content['path'];
            }
        }

        $this->progressStart('Checking for updated archives');

        foreach ($mrcFileNames as $mrcFileName) {
            $this->progressMessage('Checking for updated archive: "'.$mrcFileName.'"');
            try {
                if ($this->archiveHasUpdate($mrcFileName)) {
                    $this->progressMessage('New archive found, Downloading....');
                    $this->progressAdvance();

                    $this->updateArchive($mrcFileName);
                }

                $this->progressMessage('Getting records from archive....');
                $this->progressAdvance();

                $count = 0;
                $isbnImageUrlArray = [];
                $localArchivePath = $this->local->getAdapter()->getPathPrefix().$mrcFileName;
                $collection = Collection::fromFile($localArchivePath);

                foreach ($collection as $record) {
                    // Query for all subfield 'u' in all field '856' that also has subfield '3' (Image)
                    $imageUrl = $record->query('856$u{?856$3}')->text();
                    $isbns = $record->isbns;
                    foreach ($isbns as $isbn) {
                        $isbnImageUrlArray[$isbn->getContents()] = $imageUrl;
                        ++$count;

                        if (0 === $count % self::BATCH_SIZE) {
                            RbDigitalPublicUrlConverter::convertArrayValues($isbnImageUrlArray);
                            $this->updateOrInsertMaterials($isbnImageUrlArray);
                            $isbnImageUrlArray = [];

                            $this->progressMessageFormatted($this->totalUpdated, $this->totalInserted, $this->totalIsIdentifiers);
                            $this->progressAdvance();
                        }
                    }
                }

                RbDigitalPublicUrlConverter::convertArrayValues($isbnImageUrlArray);
                $this->updateOrInsertMaterials($isbnImageUrlArray);
                $isbnImageUrlArray = [];

                $this->progressMessageFormatted($this->totalUpdated, $this->totalInserted, $this->totalIsIdentifiers);
                $this->progressAdvance();
            } catch (\Exception $exception) {
                return VendorImportResultMessage::error($exception->getMessage());
            }
        }

        $this->logStatistics();

        $this->progressFinish();

        return VendorImportResultMessage::success($this->totalIsIdentifiers, $this->totalUpdated, $this->totalInserted, $this->totalDeleted);
    }

    /**
     * Update local copy of vendors archive.
     *
     * @param string $mrcFileName
     *   The path and name of the records file to update
     *
     * @return bool
     *
     * @throws FileNotFoundException
     * @throws InvalidArgumentException
     * @throws IllegalVendorServiceException
     */
    private function updateArchive(string $mrcFileName): bool
    {
        $remoteModifiedAt = $this->ftp->getTimestamp($mrcFileName);
        $remoteModifiedAtCache = $this->cache->getItem($this->getCacheKey($mrcFileName));
        $remoteModifiedAtCache->set($remoteModifiedAt);
        $remoteModifiedAtCache->expiresAfter(24 * 60 * 60);

        $this->cache->save($remoteModifiedAtCache);

        // @TODO Error handling for missing archive
        return $this->local->put($mrcFileName, $this->ftp->read($mrcFileName));
    }

    /**
     * Check if vendors archive has update.
     *
     * @param string $mrcFileName
     *   The path and name of the records file to check for update to
     *
     * @return bool
     *
     * @throws FileNotFoundException
     * @throws InvalidArgumentException
     * @throws IllegalVendorServiceException
     */
    private function archiveHasUpdate(string $mrcFileName): bool
    {
        $update = true;

        if ($this->local->has($mrcFileName)) {
            $remoteModifiedAtCache = $this->cache->getItem($this->getCacheKey($mrcFileName));

            if ($remoteModifiedAtCache->isHit()) {
                $remote = $this->ftp->getTimestamp(self::VENDOR_ARCHIVE_NAME);
                $update = $remote > $remoteModifiedAtCache->get();
            }
        }

        return $update;
    }

    /**
     * Get cache key for the given filename.
     *
     * @param string $mrcFileName
     *   The filename to get a cache key for
     *
     * @return string
     *
     * @throws IllegalVendorServiceException
     */
    private function getCacheKey(string $mrcFileName): string
    {
        $hash = md5($mrcFileName);

        return 'app.vendor.'.$this->getVendorId().$hash.'.remoteModifiedAt';
    }

    /**
     * Set config for service from DB vendor object.
     *
     * @throws UnknownVendorServiceException
     * @throws IllegalVendorServiceException
     */
    private function loadConfig(): void
    {
        // Set FTP adapter configuration.
        $adapter = $this->ftp->getAdapter();
        $adapter->setUsername($this->getVendor()->getDataServerUser());
        $adapter->setPassword($this->getVendor()->getDataServerPassword());
        $adapter->setHost($this->getVendor()->getDataServerURI());
    }
}
