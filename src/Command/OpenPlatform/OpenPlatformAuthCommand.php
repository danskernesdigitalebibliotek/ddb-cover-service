<?php

/**
 * @file
 * Console commands to execute and test Open Platform authentication.
 */

namespace App\Command\OpenPlatform;

use App\Service\OpenPlatform\AuthenticationService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class OpenPlatformAuthCommand.
 */
class OpenPlatformAuthCommand extends Command
{
    protected static $defaultName = 'app:openplatform:auth';

    private $authentication;
    private $refresh = false;

    /**
     * OpenPlatformAuthCommand constructor.
     *
     * @param authenticationService $authentication
     *   Open Platform authentication service
     */
    public function __construct(AuthenticationService $authentication)
    {
        $this->authentication = $authentication;

        parent::__construct();
    }

    /**
     * Define the command.
     */
    protected function configure()
    {
        $this->setDescription('Use environment configuration to test authentication')
            ->setHelp('Gets oAuth2 access token to the Open Platform')
            ->addArgument('refresh', InputArgument::OPTIONAL, 'Refresh the access token');
    }

    /**
     * {@inheritdoc}
     *
     * Uses the authentication service to get an access token form the open
     * platform.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $arg = $input->getArgument('refresh');
        $this->refresh = $arg ? (bool) $arg : $this->refresh;
        $token = $this->authentication->getAccessToken($this->refresh);

        $msg = 'Access token: '.$token;
        $separator = str_repeat('-', strlen($msg) + 2);
        $output->writeln($separator);
        $output->writeln(' Access token: '.$token);
        $output->writeln($separator);
    }
}
