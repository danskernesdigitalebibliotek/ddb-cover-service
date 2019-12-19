<?php

/**
 * @file
 * Contains ApiSearchProcessor for processing search messages.
 */

namespace App\Service\VendorService\TheMovieDatabase\Queue;

use App\Entity\Source;
use App\Entity\Vendor;
use App\Event\VendorEvent;
use App\Service\VendorService\TheMovieDatabase\Message\ApiSearchMessage;
use App\Service\VendorService\TheMovieDatabase\TheMovieDatabaseApiService;
use App\Utils\Types\IdentifierType;
use App\Utils\Types\VendorState;
use Doctrine\ORM\EntityManagerInterface;
use Enqueue\Client\TopicSubscriberInterface;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;
use Karriere\JsonDecoder\JsonDecoder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class ApiSearchProcessor.
 */
class ApiSearchProcessor implements PsrProcessor, TopicSubscriberInterface
{
    private $apiService;
    private $entityManager;
    private $dispatcher;

    /**
     * ApiSearchProcessor constructor.
     *
     * @param TheMovieDatabaseApiService $apiService
     *   The service to search with
     * @param EntityManagerInterface     $entityManager
     *   The entity manager
     * @param EventDispatcherInterface   $dispatcher
     *   The event dispatcher
     */
    public function __construct(TheMovieDatabaseApiService $apiService, EntityManagerInterface $entityManager, EventDispatcherInterface $dispatcher)
    {
        $this->apiService = $apiService;
        $this->entityManager = $entityManager;
        $this->dispatcher = $dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function process(PsrMessage $message, PsrContext $context)
    {
        $sourceRepo = $this->entityManager->getRepository(Source::class);

        $jsonDecoder = new JsonDecoder(true);

        /* @var ApiSearchMessage $apiSearchMessage */
        $apiSearchMessage = $jsonDecoder->decode($message->getBody(), ApiSearchMessage::class);

        $vendorId = $apiSearchMessage->getVendorId();
        $vendor = $this->entityManager->getRepository(Vendor::class)->find($vendorId);

        // Find source in database.
        $source = $sourceRepo->findOneBy([
            'matchId' => $apiSearchMessage->getPid(),
            'matchType' => IdentifierType::PID,
            'vendor' => $vendor,
        ]);

        // Get poster url.
        $posterUrl = $this->apiService->searchPosterUrl($apiSearchMessage->getTitle(), $apiSearchMessage->getOriginalYear(), $apiSearchMessage->getDirector());

        // Set poster url of source.
        if ($source !== null && $posterUrl !== null) {
            $source->setOriginalFile($posterUrl);
            $this->entityManager->flush();

            // Create vendor event.
            $event = new VendorEvent(VendorState::INSERT, [$source->getMatchId()], $source->getMatchType(), $source->getVendor()->getId());
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
            'ApiSearchTopic' => [
                'processorName' => 'ApiSearchProcessor',
                'queueName' => 'ApiSearchQueue',
            ],
        ];
    }
}
