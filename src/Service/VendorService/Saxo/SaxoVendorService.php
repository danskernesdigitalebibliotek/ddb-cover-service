<?php
/**
 * @file
 * Service for updating data from 'Saxo' xlsx spreadsheet.
 */

namespace App\Service\VendorService\Saxo;

use App\Service\VendorService\AbstractBaseVendorService;
use App\Service\VendorService\ProgressBarTrait;
use App\Utils\Message\VendorImportResultMessage;
use Box\Spout\Common\Type;
use Box\Spout\Reader\ReaderFactory;
use Box\Spout\Reader\XLSX\Reader;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class SaxoVendorService.
 */
class SaxoVendorService extends AbstractBaseVendorService
{
    use ProgressBarTrait;

    protected const VENDOR_ID = 3;

    private const VENDOR_ARCHIVE_DIR = 'Saxo';
    private const VENDOR_ARCHIVE_NAME = 'Danske bogforsider.xlsx';

    private $resourcesDir;

    /**
     * SaxoVendorService constructor.
     *
     * @param eventDispatcherInterface $eventDispatcher
     *   Dispatcher to trigger async jobs on import
     * @param entityManagerInterface $entityManager
     *   Doctrine entity manager
     * @param loggerInterface $statsLogger
     *   Logger object to send stats to ES
     * @param string $resourcesDir
     *   The application resource dir
     */
    public function __construct(EventDispatcherInterface $eventDispatcher, EntityManagerInterface $entityManager,
                                LoggerInterface $statsLogger, string $resourcesDir)
    {
        parent::__construct($eventDispatcher, $entityManager, $statsLogger);

        $this->resourcesDir = $resourcesDir;
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

        try {
            $this->progressStart('Opening sheet: "'.self::VENDOR_ARCHIVE_NAME.'"');

            $reader = $this->getSheetReader();

            $totalRows = 0;
            $isbnArray = [];

            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $isbn = (string) $row[0];
                    if (!empty($isbn)) {
                        $isbnArray[$isbn] = $this->getVendorsImageUrl($isbn);
                    }

                    ++$totalRows;

                    if ($limit && $totalRows >= $limit) {
                        break;
                    }

                    if (0 === $totalRows % 100) {
                        $this->updateOrInsertMaterials($isbnArray);

                        $isbnArray = [];

                        $this->progressMessageFormatted($this->totalUpdated, $this->totalInserted, $totalRows);
                        $this->progressAdvance();
                    }
                }
            }

            $this->updateOrInsertMaterials($isbnArray);

            $this->logStatistics();

            $this->progressFinish();

            return VendorImportResultMessage::success($this->totalIsIdentifiers, $this->totalUpdated, $this->totalInserted);
        } catch (\Exception $exception) {
            return VendorImportResultMessage::error($exception->getMessage());
        }
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
        return $this->getVendor()->getImageServerURI().'_'.$isbn.'/0x0';
    }

    /**
     * Get a reference a xlsx filereader reference for the import source.
     *
     * @return Reader
     *
     * @throws \Box\Spout\Common\Exception\IOException
     * @throws \Box\Spout\Common\Exception\UnsupportedTypeException
     */
    private function getSheetReader(): Reader
    {
        $resourceDirectories = [$this->resourcesDir.'/'.self::VENDOR_ARCHIVE_DIR];

        $fileLocator = new FileLocator($resourceDirectories);
        $filePath = $fileLocator->locate(self::VENDOR_ARCHIVE_NAME, null, true);

        $reader = ReaderFactory::create(Type::XLSX);
        $reader->open($filePath);

        return $reader;
    }
}
