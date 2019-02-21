<?php

/**
 * @file
 */

namespace App\Queue;

use App\Entity\Image;
use App\Entity\Source;
use App\Entity\Vendor;
use App\Exception\CoverStoreCredentialException;
use App\Exception\CoverStoreException;
use App\Exception\CoverStoreNotFoundException;
use App\Exception\CoverStoreTooLargeFileException;
use App\Exception\CoverStoreUnexpectedException;
use App\Service\CoverStore\CoverStoreInterface;
use App\Utils\Message\ProcessMessage;
use Doctrine\ORM\EntityManagerInterface;
use Enqueue\Client\ProducerInterface;
use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\Util\JSON;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;
use Karriere\JsonDecoder\JsonDecoder;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;

/**
 * Class CoverStoreProcessor.
 */
class CoverStoreProcessor implements PsrProcessor, TopicSubscriberInterface
{
    private $em;
    private $producer;
    private $statsLogger;
    private $coverStore;

    /**
     * CoverStoreProcessor constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param ProducerInterface $producer
     * @param LoggerInterface $statsLogger
     * @param CoverStoreInterface $coverStore
     */
    public function __construct(EntityManagerInterface $entityManager, ProducerInterface $producer, LoggerInterface $statsLogger, CoverStoreInterface $coverStore)
    {
        $this->em = $entityManager;
        $this->producer = $producer;
        $this->statsLogger = $statsLogger;
        $this->coverStore = $coverStore;
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

        try {
            $identifier = $processMessage->getIdentifier();
            $item = $this->coverStore->upload($source->getOriginalFile(), $vendor->getName(), $identifier, [$identifier]);
        } catch (CoverStoreCredentialException $exception) {
            // Access issues.
            $this->statsLogger->error('Access denied to cover store', [
                'service' => 'CoverStoreProcessor',
                'message' => $exception->getMessage(),
                'identifier' => $processMessage->getIdentifier(),
            ]);

            return self::REJECT;
        } catch (CoverStoreNotFoundException $exception) {
            // Update image entity and remove source URL.
            $source->setOriginalFile(null);
            $source->setOriginalLastModified(null);
            $source->setOriginalContentLength(null);
            $this->em->flush();

            // Log that the image did not exists.
            $this->statsLogger->error('Cover store error - not found', [
                'service' => 'CoverStoreProcessor',
                'message' => $exception->getMessage(),
                'identifier' => $processMessage->getIdentifier(),
                'url' => $source->getOriginalFile(),
            ]);

            return self::REJECT;
        } catch (CoverStoreTooLargeFileException $exception) {
            $this->statsLogger->error('Cover was to large', [
                'service' => 'CoverStoreProcessor',
                'message' => $exception->getMessage(),
                'identifier' => $processMessage->getIdentifier(),
                'url' => $source->getOriginalFile(),
            ]);

            return self::REJECT;
        } catch (CoverStoreUnexpectedException $exception) {
            $this->statsLogger->error('Cover store unexpected error', [
                'service' => 'CoverStoreProcessor',
                'message' => $exception->getMessage(),
                'identifier' => $processMessage->getIdentifier(),
            ]);

            return self::REJECT;
        } catch (CoverStoreException $exception) {
            $this->statsLogger->error('Cover store error - retry', [
                'service' => 'CoverStoreProcessor',
                'message' => $exception->getMessage(),
                'identifier' => $processMessage->getIdentifier(),
            ]);

            // Service issues, retry the job.
            return self::REQUEUE;
        }

        // Log information about the image uploaded.
        $this->statsLogger->info('Image cover stored', [
            'service' => 'CoverStoreProcessor',
            'provider' => $item->getVendor(),
            'url' => $item->getUrl(),
            'width' => $item->getWidth(),
            'height' => $item->getHeight(),
            'bytes' => $item->getSize(),
            'format' => $item->getImageFormat(),
        ]);

        // Get image entity, if empty create new image entity else update the
        // entity.
        $image = $source->getImage();
        if (empty($image)) {
            $image = new Image();
            $this->em->persist($image);
        } else {
            // Check that if there exists an auto-generated image. If so delete it
            // from the cover store. Search table indexes should be updated in the
            // SearchProcess job that's up next.
            if ($image->isAutoGenerated()) {
                try {
                    $this->coverStore->remove('Unknown', $processMessage->getIdentifier());
                } catch (Exception $exception) {
                    $this->statsLogger->error('Error removing auto-generated cover - replaced by real cover', [
                        'service' => 'CoverStoreProcessor',
                        'message' => $exception->getMessage(),
                        'identifier' => $processMessage->getIdentifier(),
                    ]);
                }
            }
        }

        $image->setImageFormat($item->getImageFormat())
            ->setSize($item->getSize())
            ->setWidth($item->getWidth())
            ->setHeight($item->getHeight())
            ->setCoverStoreURL($item->getUrl())
            ->setAutoGenerated(false);

        $source->setImage($image);
        $this->em->flush();

        // Send message to next part of the process.
        $processMessage->setImageId($image->getId());
        $this->producer->sendEvent('SearchTopic', JSON::encode($processMessage));

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [
            'CoverStoreTopic' => [
                'processorName' => 'CoverStoreProcessor',
                'queueName' => 'CoverStoreQueue',
            ],
        ];
    }
}
