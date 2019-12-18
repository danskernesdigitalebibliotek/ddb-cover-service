<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class StatsLoggingService.
 */
class StatsLoggingService
{
    const EMERGENCY = 'emergency';
    const ALERT = 'alert';
    const CRITICAL = 'critical';
    const ERROR = 'error';
    const WARNING = 'warning';
    const NOTICE = 'notice';
    const INFO = 'info';
    const DEBUG = 'debug';

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
     * Log message deferred until after response has been sent.
     *
     * @param string     $level
     *   Logging level
     * @param string     $message
     *   Logging message
     * @param array|null $context
     *   Array of context data
     */
    public function log($level, $message, $context = null)
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
     * Log emergency deferred until after response has been sent.
     *
     * @param string $message
     *   Logging message
     * @param array  $context
     *   Array of context data
     */
    public function emergency($message, $context)
    {
        $this->log(self::EMERGENCY, $message, $context);
    }

    /**
     * Log alert deferred until after response has been sent.
     *
     * @param string $message
     *   Logging message
     * @param array  $context
     *   Array of context data
     */
    public function alert($message, $context)
    {
        $this->log(self::ALERT, $message, $context);
    }

    /**
     * Log critical deferred until after response has been sent.
     *
     * @param string $message
     *   Logging message
     * @param array  $context
     *   Array of context data
     */
    public function critical($message, $context)
    {
        $this->log(self::CRITICAL, $message, $context);
    }

    /**
     * Log error deferred until after response has been sent.
     *
     * @param string $message
     *   Logging message
     * @param array  $context
     *   Array of context data
     */
    public function error($message, $context)
    {
        $this->log(self::ERROR, $message, $context);
    }

    /**
     * Log warning deferred until after response has been sent.
     *
     * @param string $message
     *   Logging message
     * @param array  $context
     *   Array of context data
     */
    public function warning($message, $context)
    {
        $this->log(self::WARNING, $message, $context);
    }

    /**
     * Log notice deferred until after response has been sent.
     *
     * @param string $message
     *   Logging message
     * @param array  $context
     *   Array of context data
     */
    public function notice($message, $context)
    {
        $this->log(self::NOTICE, $message, $context);
    }

    /**
     * Log info deferred until after response has been sent.
     *
     * @param string $message
     *   Logging message
     * @param array  $context
     *   Array of context data
     */
    public function info($message, $context)
    {
        $this->log(self::INFO, $message, $context);
    }

    /**
     * Log debug deferred until after response has been sent.
     *
     * @param string $message
     *   Logging message
     * @param array  $context
     *   Array of context data
     */
    public function debug($message, $context)
    {
        $this->log(self::DEBUG, $message, $context);
    }
}
