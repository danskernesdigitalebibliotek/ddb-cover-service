<?php
/**
 * @file
 * Command to populate elasticsearch.
 */

namespace App\Command;

use App\DataFixtures\AppFixtures;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class FixturesLoadCommand.
 */
class FixturesLoadCommand extends Command
{
    protected static $defaultName = 'app:fixtures:load';

    private $appFixtures;

    /**
     * FixturesLoadCommand constructor.
     *
     * @param AppFixtures $appFixtures
     */
    public function __construct(AppFixtures $appFixtures)
    {
        $this->appFixtures = $appFixtures;

        parent::__construct();
    }

    /**
     * Configure command.
     */
    protected function configure(): void
    {
        $this->setDescription('Load data fixtures in elastic search');
    }

    /**
     * Execute command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->appFixtures->load();

        $io->success('Fixtures loaded.');

        return 0;
    }
}
