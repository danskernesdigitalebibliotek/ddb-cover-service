<?php

/**
 * @file
 */

namespace App\Command\Source;

use App\Service\VendorService\VendorImageValidatorService;
use App\Utils\CoverVendor\VendorImageItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SourceUpdateImageMetaCommand extends Command
{
    protected static $defaultName = 'app:source:update-image-meta';

    private $em;
    private $validator;

    public function __construct(EntityManagerInterface $entityManager, VendorImageValidatorService $validator)
    {
        $this->em = $entityManager;
        $this->validator = $validator;

        parent::__construct();
    }

    /**
     * Define the command.
     */
    protected function configure()
    {
        $this->setDescription('Reindex search table');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $batchSize = 50;
        $i = 0;

        // @TODO: Move into repository and use query builder.
        $query = $this->em->createQuery('SELECT s FROM App\Entity\Source s WHERE s.originalFile IS NOT NULL AND s.originalContentLength IS NULL AND s.originalLastModified IS NULL');
        $iterableResult = $query->iterate();
        foreach ($iterableResult as $row) {
            $source = $row[0];

            $item = new VendorImageItem();
            $item->setOriginalFile($source->getOriginalFile());
            $this->validator->validateRemoteImage($item);

            if ($item->isFound()) {
                $source->setOriginalLastModified($item->getOriginalLastModified());
                $source->setOriginalContentLength($item->getOriginalContentLength());
            }

            // Free memory when batch size is reached.
            if (0 === ($i % $batchSize)) {
                $this->em->flush();
                $this->em->clear();
            }

            ++$i;
        }
    }
}
