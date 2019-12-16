<?php
/**
 * @file
 *
 */

namespace App\Service;

use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Prometheus\Storage\APC;

/**
 * Class MetricsService
 *
 * @package App\Service
 */
class MetricsService
{
    private $registry;

    /**
     * MetricsService constructor.
     */
    public function __construct() {
        $adapter = new APC();
        $this->registry = new CollectorRegistry($adapter);
    }

    /**
     * Test function.
     *
     * @throws \Prometheus\Exception\MetricsRegistrationException
     */
    public function metrics()
    {
        $this->registry->getOrRegisterCounter('', 'some_quick_counter', 'just a quick measurement')
            ->inc();
    }

    /**
     * Render metrics in prometheus format.
     *
     * @return string
     *   Render matrices in a single string.
     */
    public function render() {
        $renderer = new RenderTextFormat();
        return $renderer->render($this->registry->getMetricFamilySamples());
    }
}
