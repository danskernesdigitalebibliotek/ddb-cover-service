<?php

/**
 * @file
 */

namespace App\Queue;

use App\Entity\Source;
use App\Entity\Vendor;
use App\Service\VendorService\VendorImageValidatorService;
use App\Utils\CoverVendor\VendorImageItem;
use App\Utils\Message\ProcessMessage;
use App\Utils\Types\VendorState;
use Doctrine\ORM\EntityManagerInterface;
use Enqueue\Client\ProducerInterface;
use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\Util\JSON;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;
use Karriere\JsonDecoder\JsonDecoder;
use Psr\Log\LoggerInterface;

/**
 * Class CoverStoreProcessor.
 */
class VendorImageProcessor implements PsrProcessor, TopicSubscriberInterface
{
    private $em;
    private $imageValidator;
    private $producer;
    private $statsLogger;

    /**
     * VendorImageProcessor constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param VendorImageValidatorService $imageValidator
     * @param ProducerInterface $producer
     * @param LoggerInterface $statsLogger
     */
    public function __construct(EntityManagerInterface $entityManager,VendorImageValidatorService $imageValidator,
                                ProducerInterface $producer, LoggerInterface $statsLogger)
    {
        $this->em = $entityManager;
        $this->imageValidator = $imageValidator;
        $this->producer = $producer;
        $this->statsLogger = $statsLogger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(PsrMessage $message, PsrContext $session)
    {
        $jsonDecoder = new JsonDecoder(true);
        $processMessage = $jsonDecoder->decode($message->getBody(), ProcessMessage::class);

        // Look up vendor to get information about image server.
        $vendorRepos = $this->em->getRepository(Vendor::class);
        $vendor = $vendorRepos->find($processMessage->getVendorId());

        // Look up source to get source url and link it to the image.
        $sourceRepos = $this->em->getRepository(Source::class);
        $source = $sourceRepos->findOneBy([
            'matchId' => $processMessage->getIdentifier(),
            'vendor' => $vendor,
        ]);

        if ($source) {
            switch ($processMessage->getOperation()) {
                case VendorState::INSERT:
                  $processorStatus = $this->processInsert($processMessage, $source);
                  break;

                case VendorState::UPDATE:
                  $processorStatus = $this->processUpdate($processMessage, $source);
                  break;

                default:
                  $processorStatus = self::REJECT;
          }
        } else {
            $processorStatus = self::REJECT;
        }

        return $processorStatus;
    }

    /**
     * Handle image inserts. Send update to cover store processor only if vendor image exists.
     *
     * @param ProcessMessage $processMessage
     * @param Source $source
     *
     * @return string
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function processInsert(ProcessMessage $processMessage, Source $source): string
    {
        $item = new VendorImageItem();
        $item->setOriginalFile($source->getOriginalFile());

        $this->imageValidator->validateRemoteImage($item);

        $processorStatus = self::REJECT;

        if ($item->isFound()) {
            $this->producer->sendEvent('CoverStoreTopic', JSON::encode($processMessage));

            $source->setOriginalLastModified($item->getOriginalLastModified());
            $source->setOriginalContentLength($item->getOriginalContentLength());

            $processorStatus = self::ACK;
        } else {
            $source->setOriginalFile(null);
            $source->setOriginalLastModified(null);
            $source->setOriginalContentLength(null);

            // Log that the image did not exists.
            $this->statsLogger->error('Vendor image error - not found', [
                'service' => 'VendorImageProcessor',
                'identifier' => $processMessage->getIdentifier(),
                'url' => $source->getOriginalFile(),
            ]);
        }

        $this->em->flush();

        return $processorStatus;
    }

    /**
     * Handle image updates. Send update to cover store processor only if vendor image is updated.
     *
     * @param ProcessMessage $processMessage
     * @param Source $source
     *
     * @return string
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function processUpdate(ProcessMessage $processMessage, Source $source): string
    {
        $item = new VendorImageItem();
        $item->setOriginalFile($source->getOriginalFile());

        $this->imageValidator->isRemoteImageUpdated($item, $source);

        $processorStatus = self::REJECT;

        if ($item->isUpdated()) {
            $this->producer->sendEvent('CoverStoreTopic', JSON::encode($processMessage));

            $processorStatus = self::ACK;
        }

        return $processorStatus;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics(): array
    {
        return [
            'VendorImageTopic' => [
                'processorName' => 'VendorImageProcessor',
                'queueName' => 'CoverStoreQueue',
            ],
        ];
    }
}
