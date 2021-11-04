<?php
/**
 * @file
 * Service to help collect metrics and render them.
 */

namespace App\Service;

use Prometheus\CollectorRegistry;
use Prometheus\Exception\MetricsRegistrationException;
use Prometheus\RenderTextFormat;
use Prometheus\Storage\Adapter;

/**
 * Class MetricsService.
 */
class MetricsService
{
    private CollectorRegistry $registry;
    private string $namespace = 'CoverService';

    /**
     * MetricsService constructor.
     *
     * @param Adapter $adapter
     */
    public function __construct(Adapter $adapter)
    {
        $this->registry = new CollectorRegistry($adapter);
    }

    /**
     * Counter metrics.
     *
     * A counter is a cumulative metric that represents a single monotonically increasing counter whose value can only
     * increase or be reset to zero on restart. For example, you can use a counter to represent the number of requests
     * served, tasks completed, or errors.
     *
     * @param string $name
     *   The name of the metrics
     * @param string $help
     *   Helper text for the matrices
     * @param int $value
     *   The value to increment with
     * @param array $labels
     *   Labels to filter by in prometheus. Default empty array.
     */
    public function counter(string $name, string $help, int $value = 1, array $labels = []): void
    {
        try {
            $counter = $this->registry->getOrRegisterCounter($this->namespace, $name, $help, array_keys($labels));
            $counter->incBy($value, array_values($labels));
        } catch (MetricsRegistrationException $exception) {
            // Don't do anything as metrics collection should not stop execution or take more execution time than
            // needed.
        }
    }

    /**
     * Gauge metrics.
     *
     * A gauge is a metric that represents a single numerical value that can arbitrarily go up and down.
     *
     * @param string $name
     *   The name of the metrics
     * @param string $help
     *   Helper text for the matrices
     * @param int $value
     *   Value that the gauge should be set to
     * @param array $labels
     *   Labels to filter by in prometheus. Default empty array.
     */
    public function gauge(string $name, string $help, int $value, array $labels = []): void
    {
        try {
            $gauge = $this->registry->getOrRegisterGauge($this->namespace, $name, $help, array_keys($labels));
            $gauge->set($value, array_values($labels));
        } catch (MetricsRegistrationException $exception) {
            // Don't do anything as metrics collection should not stop execution or take more execution time than
            // needed.
        }
    }

    /**
     * Histogram metrics.
     *
     * A histogram samples observations (usually things like request durations or response sizes) and counts them in
     * configurable buckets. It also provides a sum of all observed values.
     *
     * @param string $name
     *   The name of the metrics
     * @param string $help
     *   Helper text for the matrices
     * @param float $value
     *   The value that should be added to the histogram
     * @param array $labels
     *   Labels to filter by in prometheus. Default empty array.
     */
    public function histogram(string $name, string $help, float $value, array $labels = []): void
    {
        try {
            $histogram = $this->registry->getOrRegisterHistogram($this->namespace, $name, $help, array_keys($labels));
            $histogram->observe($value, array_values($labels));
        } catch (MetricsRegistrationException $exception) {
            // Don't do anything as metrics collection should not stop execution or take more execution time than
            // needed.
        }
    }

    /**
     * Render metrics in prometheus format.
     *
     * @return string
     *   Render matrices in a single string
     */
    public function render(): string
    {
        $renderer = new RenderTextFormat();

        return $renderer->render($this->registry->getMetricFamilySamples());
    }
}
