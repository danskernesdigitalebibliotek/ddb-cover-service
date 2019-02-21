<?php

/**
 * @file
 * Console commands to execute and test Open Platform search.
 */

namespace App\Command\OpenPlatform;

use App\Service\OpenPlatform\SearchService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class OpenPlatformSearchCommand.
 */
class OpenPlatformSearchCommand extends Command
{
    protected static $defaultName = 'app:openplatform:search';

    private $search;
    private $refresh = false;

    /**
     * OpenPlatformSearchCommand constructor.
     *
     * @param searchService $search
     *   The open platform search service
     */
    public function __construct(SearchService $search)
    {
        $this->search = $search;

        parent::__construct();
    }

    /**
     * Define the command.
     */
    protected function configure()
    {
        $this->setDescription('Use environment configuration to test search')
            ->setHelp('Try search request against the open platform')
            ->addArgument('is', InputArgument::REQUIRED, 'The material id (isbn, faust, pid)')
            ->addArgument('type', InputArgument::REQUIRED, 'Identifier type e.g. ISBN.')
            ->addArgument('refresh', InputArgument::OPTIONAL, 'Refresh the access token');
    }

    /**
     * {@inheritdoc}
     *
     * Execute an data well search and output the result.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $refresh = $input->getArgument('refresh');
        $this->refresh = $refresh ? (bool) $refresh : $this->refresh;
        $is = $input->getArgument('is');
        $type = $input->getArgument('type');

        $material = $this->search->search($is, $type, $this->refresh);
        $output->writeln($material);
    }
}
