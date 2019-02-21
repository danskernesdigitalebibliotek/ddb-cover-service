<?php
/**
 * @file
 * Console command to populate vendor config table with missing vendors services.
 */

namespace App\Command\Vendors;

use App\Service\VendorService\VendorServiceFactory;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class VendorPopulateCommand extends Command
{
    protected static $defaultName = 'app:vendor:populate';

    private $vendorFactory;

    /**
     * VendorPopulateCommand constructor.
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
    protected function configure()
    {
        $this->setDescription('Populate vendor options table in DB with missing vendor services.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $inserted = $this->vendorFactory->populateVendors();

            $io->success('👍 '.$inserted.' vendors inserted.');
        } catch (Exception $exception) {
            $io->error('👎 '.$exception->getMessage());

            return;
        }
    }
}
