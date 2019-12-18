<?php

/**
 * @file
 * Contains the statistics logger.
 */

namespace App\Service;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class StatsLoggingService.
 */
class StatsLoggingService extends LogLevel implements LoggerInterface
{
    private $logger;
    private $dispatcher;

    /**
     * StatsLoggingService constructor.
     *
     * @param LoggerInterface          $statsLogger
     *   The logger
     * @param EventDispatcherInterface $dispatcher
     *   The event dispatcher
     */
    public function __construct(LoggerInterface $statsLogger, EventDispatcherInterface $dispatcher)
    {
        $this->logger = $statsLogger;
        $this->dispatcher = $dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = [])
    {
        $logger = $this->logger;

        $this->dispatcher->addListener(
            KernelEvents::TERMINATE,
            function (TerminateEvent $event) use ($level, $logger, $message, $context) {
                switch ($level) {
                    case self::EMERGENCY:
                        $logger->emergency($message, $context);
                        break;
                    case self::ALERT:
                        $logger->alert($message, $context);
                        break;
                    case self::CRITICAL:
                        $logger->critical($message, $context);
                        break;
                    case self::ERROR:
                        $logger->error($message, $context);
                        break;
                    case self::WARNING:
                        $logger->warning($message, $context);
                        break;
                    case self::NOTICE:
                        $logger->notice($message, $context);
                        break;
                    case self::INFO:
                        $logger->info($message, $context);
                        break;
                    case self::DEBUG:
                        $logger->debug($message, $context);
                        break;
                }
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function emergency($message, array $context = [])
    {
        $this->log(self::EMERGENCY, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function alert($message, array $context = [])
    {
        $this->log(self::ALERT, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function critical($message, array $context = [])
    {
        $this->log(self::CRITICAL, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function error($message, array $context = [])
    {
        $this->log(self::ERROR, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function warning($message, array $context = [])
    {
        $this->log(self::WARNING, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function notice($message, array $context = [])
    {
        $this->log(self::NOTICE, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function info($message, array $context = [])
    {
        $this->log(self::INFO, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function debug($message, array $context = [])
    {
        $this->log(self::DEBUG, $message, $context);
    }
}
