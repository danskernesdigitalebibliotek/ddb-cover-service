<?php
/**
 * @file
 * Console command to load vendor data
 */

namespace App\Command\Vendors;

use App\Service\VendorService\VendorServiceFactory;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class VendorCommand.
 */
class VendorCommand extends Command
{
    protected static $defaultName = 'app:vendor:load';

    private $vendorFactory;

    /**
     * VendorCommand constructor.
     *
     * @param VendorServiceFactory $vendorFactory
     */
    public function __construct(VendorServiceFactory $vendorFactory)
    {
        $this->vendorFactory = $vendorFactory;

        parent::__construct();
    }

    /**
     * Define the command.
     */
    protected function configure(): void
    {
        $this->setDescription('Load all sources/covers from known vendors');
        $this->addOption('queue', null, InputOption::VALUE_OPTIONAL, 'Should the imported data be sent into the queues - image uploader');
        $this->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Limit the amount of records imported per vendor');
        $this->addOption('vendor', null, InputOption::VALUE_OPTIONAL, 'Which Vendor should be loaded');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $queue = $input->getOption('queue');
        $queue = 'false' !== $queue;

        $limit = $input->getOption('limit');
        $limit = $limit ?: 0;

        $vendor = $input->getOption('vendor');
        // Ask 'all', 'none' or '<vendor>'
        if (empty($vendor)) {
            $vendor = $this->askForVendor($input, $output);
        }

        $vendorServices = [];
        // Check for 'all' answer
        if ('all' === $vendor) {
            $vendorServices = $this->vendorFactory->getVendorServices();
        // If answer is not 'none' it must be specific vendor
        } elseif ('none' !== $vendor) {
            $vendorServices[] = $this->vendorFactory->getVendorServiceByName($vendor);
        }
        // Answer is 'none'

        $io = new SymfonyStyle($input, $output);

        $section = $output->section('Sheet');
        $progressBarSheet = new ProgressBar($section);
        $progressBarSheet->setFormat('[%bar%] %elapsed% (%memory%) - %message%');

        $results = [];
        foreach ($vendorServices as $vendorService) {
            try {
                $vendorService->setProgressBar($progressBarSheet);
                $results[$vendorService->getVendorName()] = $vendorService->load($queue, $limit);
            } catch (Exception $exception) {
                $io->error('👎 '.$exception->getMessage());
            }
        }

        $this->outputTable($results, $output);
    }

    /**
     * Output question about which vendor to load.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return mixed
     */
    private function askForVendor(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');

        $names = $this->vendorFactory->getVendorNames();

        $question = new ChoiceQuestion('Please choose the vendor to load:',
            array_merge(['none'], $names, ['all']),
            0
        );
        $question->setErrorMessage('Vendor %s is invalid.');

        return $helper->ask($input, $output, $question);
    }

    /**
     * Output formatted table with one row per service load() result.
     *
     * @param array $results
     * @param OutputInterface $output
     */
    private function outputTable(array $results, OutputInterface $output): void
    {
        $table = new Table($output);
        $table->setStyle('box');
        $table->setHeaders(['Succes', 'Vendor', 'Message']);

        $rows = [];
        foreach ($results as $name => $result) {
            $success = $this->getSuccessString($result->isSuccess());
            $rows[] = [$success, $name, $result->getMessage()];
        }
        $table->setRows($rows);

        $table->render();
    }

    /**
     * Get succes/failure emoji.
     *
     * @param bool $success
     *
     * @return string
     */
    private function getSuccessString(bool $success): string
    {
        return $success ? '✅' : '❌';
    }
}
