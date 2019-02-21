<?php

/**
 * @file
 * Console command to test cover store upload.
 */

namespace App\Command\CoverStore;

use App\Service\CoverStore\CoverStoreInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class OpenPlatformAuthCommand.
 */
class CoverStoreUploadCommand extends Command
{
    protected static $defaultName = 'app:cover:upload';

    private $store;

    /**
     * CoverStoreUploadCommand constructor.
     *
     * @param CoverStoreInterface $store
     */
    public function __construct(CoverStoreInterface $store)
    {
        $this->store = $store;

        parent::__construct();
    }

    /**
     * Define the command.
     */
    protected function configure()
    {
        $this->setDescription('Upload image to cover store form remote URL')
            ->addArgument('url', InputArgument::REQUIRED, 'URL to image to upload')
            ->addArgument('folder', InputArgument::REQUIRED, 'Name of the vendor that owns the image')
            ->addArgument('identifier', InputArgument::REQUIRED, 'Identifier for the material to search for')
            ->addArgument('tags', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Tags to identify the image later on');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $item = $this->store->upload(
            $input->getArgument('url'),
            $input->getArgument('folder'),
            $input->getArgument('identifier'),
            $input->getArgument('tags')
        );

        $output->writeln($item);
    }
}
