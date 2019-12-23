<?php

/**
 * @file
 */

namespace App\Command\Search;

use App\Entity\Vendor;
use App\Service\VendorService\VendorServiceFactory;
use App\Utils\Message\ProcessMessage;
use App\Utils\Types\VendorState;
use Doctrine\ORM\EntityManagerInterface;
use Enqueue\Client\ProducerInterface;
use Enqueue\Util\JSON;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SearchReindexCommand extends Command
{
    protected static $defaultName = 'app:search:reindex';

    private $em;
    private $producer;

    public function __construct(EntityManagerInterface $entityManager, ProducerInterface $producer)
    {
        $this->em = $entityManager;
        $this->producer = $producer;

        parent::__construct();
    }

    /**
     * Define the command.
     */
    protected function configure()
    {
        $this->setDescription('Reindex search table')
            ->addArgument('vendorid', InputArgument::OPTIONAL, 'Limit the re-index to vendor with this id number');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $vendorId = $input->getArgument('vendorid');

        $batchSize = 50;
        $i = 0;

        // @TODO: Move into repository and use query builder.
        $query = 'SELECT s FROM App\Entity\Source s WHERE s.image IS NOT NULL';
        if ($vendorId) {
            $query .= ' AND s.vendor = ' . $vendorId;
        }
        $query = $this->em->createQuery($query);
        $iterableResult = $query->iterate();
        foreach ($iterableResult as $row) {
            $source = $row[0];

            // Build and create new search job which will trigger index event.
            $processMessage = new ProcessMessage();
            $processMessage->setIdentifier($source->getMatchId())
                ->setOperation(VendorState::UPDATE)
                ->setIdentifierType($source->getMatchType())
                ->setVendorId($source->getVendor()->getId())
                ->setImageId($source->getImage()->getId());
            $this->producer->sendEvent('SearchTopic', JSON::encode($processMessage));

            // Free memory when batch size is reached.
            if (0 === ($i % $batchSize)) {
                $this->em->clear();
            }

            ++$i;
        }
    }
}
