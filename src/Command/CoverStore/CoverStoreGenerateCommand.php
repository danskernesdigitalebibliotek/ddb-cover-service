<?php

/**
 * @file
 * Console commands to execute a search and generate cover image base on the
 * result.
 */

namespace App\Command\CoverStore;

use App\Service\CoverStore\CoverStoreInterface;
use App\Service\OpenPlatform\SearchService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class OpenPlatformAuthCommand.
 */
class CoverStoreGenerateCommand extends Command
{
    protected static $defaultName = 'app:cover:generate';

    private $coverStore;
    private $searchService;

    /**
     * CoverStoreUploadCommand constructor.
     *
     * @param CoverStoreInterface $coverStore
     * @param SearchService $searchService
     */
    public function __construct(CoverStoreInterface $coverStore, SearchService $searchService)
    {
        $this->coverStore = $coverStore;
        $this->searchService = $searchService;

        parent::__construct();
    }

    /**
     * Define the command.
     */
    protected function configure()
    {
        $this->setDescription('Upload image to cover store form remote URL')
          ->addArgument('folder', InputArgument::REQUIRED, 'Name of the vendor that owns the image')
          ->addArgument('identifier', InputArgument::REQUIRED, 'Identifier for the material to search for')
          ->addArgument('type', InputArgument::REQUIRED, 'Identifier type e.g. ISBN.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $identifier = $input->getArgument('identifier');
        $folder = $input->getArgument('folder');
        $type = $input->getArgument('type');
        $material = $this->searchService->search($identifier, $type);

        $item = $this->coverStore->generate($material, $folder, $identifier);

        $output->writeln($item);
    }
}
