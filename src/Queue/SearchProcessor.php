<?php

/**
 * @file
 */

namespace App\Queue;

use App\Event\IndexReadyEvent;
use App\Exception\MaterialTypeException;
use App\Exception\PlatformSearchException;
use App\Service\OpenPlatform\SearchService;
use App\Utils\Message\ProcessMessage;
use Doctrine\ORM\EntityManagerInterface;
use Enqueue\Client\TopicSubscriberInterface;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;
use Karriere\JsonDecoder\JsonDecoder;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class SearchProcessor.
 */
class SearchProcessor implements PsrProcessor, TopicSubscriberInterface
{
    private $em;
    private $dispatcher;
    private $statsLogger;
    private $searchService;

    /**
     * SearchProcessor constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param EventDispatcherInterface $eventDispatcher
     * @param LoggerInterface $statsLogger
     * @param SearchService $searchService
     */
    public function __construct(EntityManagerInterface $entityManager, EventDispatcherInterface $eventDispatcher, LoggerInterface $statsLogger, SearchService $searchService)
    {
        $this->em = $entityManager;
        $this->dispatcher = $eventDispatcher;
        $this->statsLogger = $statsLogger;
        $this->searchService = $searchService;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \App\Exception\PlatformAuthException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function process(PsrMessage $message, PsrContext $session)
    {
        $jsonDecoder = new JsonDecoder(true);
        $processMessage = $jsonDecoder->decode($message->getBody(), ProcessMessage::class);

        try {
            $material = $this->searchService->search($processMessage->getIdentifier(), $processMessage->getIdentifierType());
        } catch (PlatformSearchException $e) {
            $this->statsLogger->error('Search request exception', [
                'service' => 'SearchProcessor',
                'message' => $e->getMessage(),
            ]);

            return self::REQUEUE;
        } catch (MaterialTypeException $e) {
            $this->statsLogger->error('Unknown material type found', [
                'service' => 'SearchProcessor',
                'message' => $e->getMessage(),
                'identifier' => $processMessage->getIdentifier(),
                'imageId' => $processMessage->getImageId(),
            ]);

            return self::REJECT;
        }

        // Check if this was an zero hit search.
        if ($material->isEmpty()) {
            $this->statsLogger->info('Search zero-hit', [
                'service' => 'SearchProcessor',
                'identifier' => $processMessage->getIdentifier(),
                'imageId' => $processMessage->getImageId(),
            ]);

            return self::REJECT;
        } else {
            $event = new IndexReadyEvent();
            $event->setIs($processMessage->getIdentifier())
                ->setOperation($processMessage->getOperation())
                ->setVendorId($processMessage->getVendorId())
                ->setImageId($processMessage->getImageId())
                ->setMaterial($material);

            $this->dispatcher->dispatch($event::NAME, $event);
        }

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [
            'SearchTopic' => [
                'processorName' => 'SearchProcessor',
                'queueName' => 'SearchQueue',
            ],
        ];
    }
}
