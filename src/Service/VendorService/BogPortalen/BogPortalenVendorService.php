<?php
/**
 * @file
 * Service for updating data from 'Bogportalen'.
 */

namespace App\Service\VendorService\BogPortalen;

use App\Service\VendorService\AbstractBaseVendorService;
use App\Service\VendorService\ProgressBarTrait;
use App\Utils\Message\VendorImportResultMessage;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\Filesystem;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

/**
 * Class BogPortalenVendorService.
 */
class BogPortalenVendorService extends AbstractBaseVendorService
{
    use ProgressBarTrait;

    protected const VENDOR_ID = 1;
    private const VENDOR_ARCHIVE_NAME = 'BOP-ProductAll.zip';

    private $local;
    private $ftp;
    private $cache;

    /**
     * BogPortalenVendorService constructor.
     *
     * @param eventDispatcherInterface $eventDispatcher
     *   Dispatcher to trigger async jobs on import
     * @param filesystem $local
     *   Flysystem adapter for local filesystem
     * @param filesystem $ftp
     *   Flysystem adapter for remote ftp server
     * @param entityManagerInterface $entityManager
     *   Doctrine entity manager
     * @param loggerInterface $statsLogger
     *   Logger object to send stats to ES
     * @param AdapterInterface $cache
     *   Cache adapter for the application
     */
    public function __construct(EventDispatcherInterface $eventDispatcher, Filesystem $local,
                                Filesystem $ftp, EntityManagerInterface $entityManager,
                                LoggerInterface $statsLogger, AdapterInterface $cache)
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

        $this->progressStart('Checking for updated archive: "'.self::VENDOR_ARCHIVE_NAME.'"');

        try {
            if ($this->archiveHasUpdate()) {
                $this->progressMessage('New archive found, Downloading....');
                $this->progressAdvance();

                $this->updateArchive();
            }

            $this->progressMessage('Getting filenames from archive....');
            $this->progressAdvance();

            $localArchivePath = $this->local->getAdapter()->getPathPrefix().self::VENDOR_ARCHIVE_NAME;
            $files = $this->listZipContents($localArchivePath);
            $isbnList = $this->getIsbnNumbers($files);

            $this->progressMessage('Removing ISBNs not found in archive');
            $this->progressAdvance();

            // @TODO Dispatch delete event to deleteProcessor
            // $deleted = $this->deleteRemovedMaterials($isbnList);

            $offset = 0;
            $count = $limit ?: \count($isbnList);

            while ($offset < $count) {
                $isbnBatch = \array_slice($isbnList, $offset, self::BATCH_SIZE, true);

                $isbnImageUrlArray = $this->buildIsbnImageUrlArray($isbnBatch);
                $this->updateOrInsertMaterials($isbnImageUrlArray);

                $this->progressMessageFormatted($this->totalUpdated, $this->totalInserted, $this->totalIsIdentifiers);
                $this->progressAdvance();

                $offset += self::BATCH_SIZE;
            }

            $this->logStatistics();

            $this->progressFinish();

            return VendorImportResultMessage::success($this->totalIsIdentifiers, $this->totalUpdated, $this->totalInserted, $this->totalDeleted);
        } catch (\Exception $exception) {
            return VendorImportResultMessage::error($exception->getMessage());
        }
    }

    /**
     * Set config from service from DB vendor object.
     *
     * @throws \App\Exception\UnknownVendorServiceException
     * @throws \App\Exception\IllegalVendorServiceException
     */
    private function loadConfig(): void
    {
        // Set FTP adapter configuration.
        $adapter = $this->ftp->getAdapter();
        $adapter->setUsername($this->getVendor()->getDataServerUser());
        $adapter->setPassword($this->getVendor()->getDataServerPassword());
        $adapter->setHost($this->getVendor()->getDataServerURI());
    }

    /**
     * Build array of image urls keyed by isbn.
     *
     * @param array $isbnList
     *
     * @return array
     *
     * @throws \Exception
     */
    private function buildIsbnImageUrlArray(array &$isbnList): array
    {
        $isbnArray = [];
        foreach ($isbnList as $isbn) {
            $isbnArray[$isbn] = $this->getVendorsImageUrl($isbn);
        }

        return $isbnArray;
    }

    /**
     * Get Vendors image URL from ISBN.
     *
     * @param string $isbn
     *
     * @return string
     *
     * @throws \App\Exception\UnknownVendorServiceException
     * @throws \App\Exception\IllegalVendorServiceException
     */
    private function getVendorsImageUrl(string $isbn): string
    {
        return $this->getVendor()->getImageServerURI().$isbn.'.jpg';
    }

    /**
     * Check if vendors archive has update.
     *
     * @return bool
     *
     * @throws \League\Flysystem\FileNotFoundException
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \App\Exception\IllegalVendorServiceException
     */
    private function archiveHasUpdate(): bool
    {
        $update = true;

        if ($this->local->has(self::VENDOR_ARCHIVE_NAME)) {
            $remoteModifiedAtCache = $this->cache->getItem('app.vendor.'.$this->getVendorId().'.remoteModifiedAt');

            if ($remoteModifiedAtCache->isHit()) {
                $remote = $this->ftp->getTimestamp(self::VENDOR_ARCHIVE_NAME);
                $update = $remote > $remoteModifiedAtCache->get();
            }
        }

        return $update;
    }

    /**
     * Update local copy of vendors archive.
     *
     * @return bool
     *
     * @throws \League\Flysystem\FileNotFoundException
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \App\Exception\IllegalVendorServiceException
     */
    private function updateArchive(): bool
    {
        $remoteModifiedAt = $this->ftp->getTimestamp(self::VENDOR_ARCHIVE_NAME);
        $remoteModifiedAtCache = $this->cache->getItem('app.vendor.'.$this->getVendorId().'.remoteModifiedAt');
        $remoteModifiedAtCache->set($remoteModifiedAt);
        $remoteModifiedAtCache->expiresAfter(24 * 60 * 60);

        $this->cache->save($remoteModifiedAtCache);

        // @TODO Error handling for missing archive
        return $this->local->put(self::VENDOR_ARCHIVE_NAME, $this->ftp->read(self::VENDOR_ARCHIVE_NAME));
    }

    /**
     * Get list of files in ZIP archive.
     *
     * @param $path
     *   The path of the archive in the local filesystem
     *
     * @return array
     *   List of filenames
     *
     * @throws FileNotFoundException
     */
    private function listZipContents($path): array
    {
        $fileNames = [];

        // Using the native PHP function to extract the file names because we
        // don't care about metadata. This has significantly better performance
        // then the equivalent Flysystem method because the Flysystem method
        // also extracts metadata for all files.
        $zip = new \ZipArchive();
        $zipReader = $zip->open($path);

        if ($zipReader) {
            for ($i = 0; $i < $zip->numFiles; ++$i) {
                $fileNames[] = $zip->getNameIndex($i);
            }
        } else {
            throw new FileNotFoundException('Error: '.$zipReader.' when reading '.$path);
        }

        return $fileNames;
    }

    /**
     * Get valid and unique ISBN numbers from list of filenames.
     *
     * @param array $fileNames
     *
     * @return array
     */
    private function getIsbnNumbers(array &$fileNames): array
    {
        $isbnList = [];

        foreach ($fileNames as $fileName) {
            $isbn = substr($fileName, -17, 13);

            // Ensure that the found string is a number to filter out
            // files with wrong or incomplete isbn numbers.
            $temp = (int) $isbn;
            $temp = (string) $temp;
            if (($isbn === $temp) && (13 === strlen($isbn))) {
                $isbnList[] = $isbn;
            }
        }

        // Ensure there are no duplicate values in the array.
        // Double 'array_flip' performs 150x faster than 'array_unique'
        // https://stackoverflow.com/questions/8321620/array-unique-vs-array-flip
        return array_flip(array_flip($isbnList));
    }
}
