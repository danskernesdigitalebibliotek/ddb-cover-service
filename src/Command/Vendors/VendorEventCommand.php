<?php

/**
 * @file
 */

namespace App\Command\Vendors;

use App\Event\VendorEvent;
use App\Utils\Types\VendorState;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class VendorEventCommand extends Command
{
    protected static $defaultName = 'app:vendor:event-test';

    private $dispatcher;

    public function __construct(EventDispatcherInterface $EventDispatcher)
    {
        $this->dispatcher = $EventDispatcher;

        parent::__construct();
    }

    /**
     * Define the command.
     */
    protected function configure()
    {
        $this->setDescription('Send event to test import job')
            ->addArgument('event', InputArgument::REQUIRED, 'The event type to dispatch (insert/update).')
            ->addArgument('identifier', InputArgument::REQUIRED, 'Material identifier.')
            ->addArgument('type', InputArgument::REQUIRED, 'Identifier type e.g. ISBN.')
            ->addArgument('vendorId', InputArgument::REQUIRED, 'Vendor id found in the database');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $eventType = $input->getArgument('event');
        $identifier = $input->getArgument('identifier');
        $type = $input->getArgument('type');
        $vendorId = $input->getArgument('vendorId');

        switch ($eventType) {
          case VendorState::INSERT:
          case VendorState::UPDATE:
              $event = new VendorEvent($eventType, [$identifier], $type, $vendorId);
              break;

          case VendorState::DELETE:
              $event = new VendorEvent($eventType, [$identifier], $type, $vendorId);
              break;

          default:
              $output->writeln('Unknown event type given as input.');

              return -1;
              break;
        }

        $this->dispatcher->dispatch($event::NAME, $event);
    }
}
