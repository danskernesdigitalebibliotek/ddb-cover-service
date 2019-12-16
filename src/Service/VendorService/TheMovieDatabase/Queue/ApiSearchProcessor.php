<?php

namespace App\Service\VendorService\TheMovieDatabase\Queue;

use App\Entity\Source;
use App\Entity\Vendor;
use App\Service\VendorService\TheMovieDatabase\Message\ApiSearchMessage;
use App\Service\VendorService\TheMovieDatabase\TheMovieDatabaseApiService;
use App\Utils\Types\IdentifierType;
use Doctrine\Common\Persistence\ManagerRegistry;
use Enqueue\Client\TopicSubscriberInterface;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;
use Karriere\JsonDecoder\JsonDecoder;

class ApiSearchProcessor implements PsrProcessor, TopicSubscriberInterface
{
    private $apiService;
    private $managerRegistry;

    public function __construct(TheMovieDatabaseApiService $apiService, ManagerRegistry $managerRegistry)
    {
        $this->apiService = $apiService;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function process(PsrMessage $message, PsrContext $context)
    {
        $entityManager = $this->managerRegistry->getManagerForClass(Source::class);
        $sourceRepo = $entityManager->getRepository(Source::class);

        $jsonDecoder = new JsonDecoder(true);
        $apiSearchMessage = $jsonDecoder->decode($message->getBody(), ApiSearchMessage::class);
        $pidList = [$apiSearchMessage->getPid()];

        foreach ($this->managerRegistry->getConnections() as $connection) {
            if ($connection->isConnected()) {
                continue;
            }
//            if ($connection->ping()) {
//                continue;
//            }

            $connection->close();
            $connection->connect();

        }

        // @TODO Fix "MySQL server has gone away" error so we can read/write to DB

        $vendorId = $apiSearchMessage->getVendorId();
        $d = $entityManager->find(Source::class, 6);
        $vendor = $entityManager->find(Vendor::class, '6');

        $source = $sourceRepo->findByMatchIdList(IdentifierType::PID, $pidList, $vendor);
        $posterUrl = $this->apiService->searchPosterUrl($apiSearchMessage->getTitle(), $apiSearchMessage->getDate());

        if ($posterUrl) {
            $source->setOriginalFile($posterUrl);
            $this->entityManager->flush();
        }

        // @TODO Send 'VendorEvent' to trigger further processing.
        // See https://github.com/danskernesdigitalebibliotek/ddb-cover-service/blob/94131ecdfc698084caa7a32c129a95376ac6bf7d/src/Service/VendorService/AbstractBaseVendorService.php#L244-L254
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
