<?php

/**
 * @file
 * 'Publizon' vendor import service
 */

namespace App\Service\VendorService\Publizon;

use App\Exception\IllegalVendorServiceException;
use App\Exception\UnknownVendorServiceException;
use App\Service\VendorService\AbstractBaseVendorService;
use App\Service\VendorService\ProgressBarTrait;
use App\Utils\Message\VendorImportResultMessage;
use App\Utils\Types\OnixOutputDefinition;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class PublizonVendorService.
 */
class PublizonVendorService extends AbstractBaseVendorService
{
    use ProgressBarTrait;

    protected const VENDOR_ID = 5;

    private $xml;

    private $apiEndpoint;
    private $apiServiceKey;

    /**
     * {@inheritdoc}
     */
    public function __construct(EventDispatcherInterface $eventDispatcher, EntityManagerInterface $entityManager,
                                LoggerInterface $statsLogger, PublizonXmlReaderService $xmlReader)
    {
        parent::__construct($eventDispatcher, $entityManager, $statsLogger);

        $this->xml = $xmlReader;
    }

    /**
     * {@inheritdoc}
     */
    public function load(bool $queue = true, int $limit = null): VendorImportResultMessage
    {
        if (!$this->acquireLock()) {
            return VendorImportResultMessage::error(parent::ERROR_RUNNING);
        }

        $this->loadConfig();

        $this->progressStart('Opening xml resource stream from: '.$this->apiEndpoint);

        $this->xml->open($this->apiServiceKey, $this->apiEndpoint);

        $totalProducts = 0;
        $isbnArray = [];

        /*
         * We're streaming a large (approx. 500 million characters) xml document containing
         * a list of <Product> tags of the following structure:
         *
         *   <Product datestamp="20181126T10:29:40Z">
         *     ...
         *     <ProductIdentifier>
         *       <ProductIDType>15</ProductIDType>
         *       <IDValue>9788771650792</IDValue>
         *     </ProductIdentifier>
         *     ...
         *     <CollateralDetail>
         *       ...
         *       <SupportingResource>
         *         <ResourceContentType>01</ResourceContentType>
         *         <ContentAudience>00</ContentAudience>
         *         <ResourceMode>03</ResourceMode>
         *         <ResourceVersion>
         *           <ResourceForm>01</ResourceForm>
         *           <ResourceLink>https://images.pubhub.dk/originals/b5766a8e-f79a-473d-a9b6-28efb0536a10.jpg</ResourceLink>
         *         </ResourceVersion>
         *       </SupportingResource>
         *     </CollateralDetail>
         *     ...
         *   </Product>
         *
         * Given that we're streaming we can't use normal XML -> Object functions because we never
         * hold a complete element in memory.
         */

        while ($this->xml->read()) {
            $productIDType = $idValue = null;
            $resourceContentType = $resourceMode = $resourceForm = $resourceLink = null;

            if ($this->xml->isAtElementStart('Product')) {
                while ($this->xml->readUntilElementEnd('Product')) {
                    if ($this->xml->isAtElementStart('ProductIdentifier')) {
                        while ($this->xml->readUntilElementEnd('ProductIdentifier')) {
                            if ($this->xml->isAtElementStart('ProductIDType')) {
                                $productIDType = $this->xml->getNextElementValue();
                            }

                            if ($this->xml->isAtElementStart('IDValue')) {
                                $idValue = $this->xml->getNextElementValue();
                            }
                        }
                    }

                    if ($this->xml->isAtElementStart('CollateralDetail')) {
                        while ($this->xml->readUntilElementEnd('CollateralDetail')) {
                            if ($this->xml->isAtElementStart('SupportingResource')) {
                                while ($this->xml->readUntilElementEnd('SupportingResource')) {
                                    if ($this->xml->isAtElementStart('ResourceContentType')) {
                                        $resourceContentType = $this->xml->getNextElementValue();
                                    }

                                    if ($this->xml->isAtElementStart('ResourceMode')) {
                                        $resourceMode = $this->xml->getNextElementValue();
                                    }

                                    if ($this->xml->isAtElementStart('ResourceVersion')) {
                                        while ($this->xml->readUntilElementEnd('ResourceVersion')) {
                                            if ($this->xml->isAtElementStart('ResourceForm')) {
                                                $resourceForm = $this->xml->getNextElementValue();
                                            }

                                            if ($this->xml->isAtElementStart('ResourceLink')) {
                                                $resourceLink = $this->xml->getNextElementValue();
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                // Check if the we have found an ISBN number and a matching front cover
                if (OnixOutputDefinition::ISBN_13 === $productIDType && OnixOutputDefinition::FRONT_COVER === $resourceContentType
                    && OnixOutputDefinition::LINKABLE_RESOURCE === $resourceForm && OnixOutputDefinition::IMAGE === $resourceMode) {
                    $isbnArray[$idValue] = $resourceLink;
                }
                ++$totalProducts;
            }

            if ($limit && $totalProducts >= $limit) {
                break;
            }

            if (0 === $totalProducts % 100) {
                $this->updateOrInsertMaterials($isbnArray);

                $isbnArray = [];

                $this->progressMessageFormatted($this->totalUpdated, $this->totalInserted, $totalProducts);
                $this->progressAdvance();
            }
        }

        $this->updateOrInsertMaterials($isbnArray);

        $this->logStatistics();

        $this->progressFinish();

        return VendorImportResultMessage::success($this->totalIsIdentifiers, $this->totalUpdated, $this->totalInserted);
    }

    /**
     * Set config from service from DB vendor object.
     *
     * @throws UnknownVendorServiceException
     * @throws IllegalVendorServiceException
     */
    private function loadConfig(): void
    {
        $this->apiServiceKey = $this->getVendor()->getDataServerPassword();
        $this->apiEndpoint = $this->getVendor()->getDataServerURI();
    }
}
