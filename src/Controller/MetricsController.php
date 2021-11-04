<?php
/**
 * @file
 * Controller to expose application matrices for prometheus.
 */

namespace App\Controller;

use App\Service\MetricsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class MetricsController.
 */
class MetricsController extends AbstractController
{
    /**
     * @Route("/metrics", name="metrics")
     *
     * Render metrics collected by the application.
     *
     * @param MetricsService $metricsService
     *   The service used to collection data in the application
     *
     * @return response
     *   HTTP response to send back to the client
     */
    public function metrics(MetricsService $metricsService): Response
    {
        // Get stats for opcache.
        $this->opcacheMetrics($metricsService);
        $this->apcuMetrics($metricsService);

        return new Response($metricsService->render(), Response::HTTP_OK, ['content-type' => 'text/plain']);
    }

    /**
     * Get opcache statistics.
     *
     * @param MetricsService $metricsService
     *
     * @return void
     */
    private function opcacheMetrics(MetricsService $metricsService): void
    {
        $status = opcache_get_status();

        // Basic information.
        $metricsService->gauge('php_opcache_enabled', 'opcache enabled', (int) $status['opcache_enabled']);
        $metricsService->gauge('php_opcache_full', 'Is opcache full', (int) $status['cache_full']);
        $metricsService->gauge('php_opcache_restart_pending', 'Is opcache restart pending', (int) $status['restart_pending']);
        $metricsService->gauge('php_opcache_restart_in_progress', 'Is opcache restart in progress', (int) $status['restart_in_progress']);

        // Memory usage.
        $metricsService->gauge('php_opcache_used_memory_bytes', 'Used memory', $status['memory_usage']['used_memory']);
        $metricsService->gauge('php_opcache_free_memory_bytes', 'Used memory', $status['memory_usage']['free_memory']);
        $metricsService->gauge('php_opcache_wasted_memory_bytes', 'Used memory', $status['memory_usage']['wasted_memory']);
        $metricsService->gauge('php_opcache_current_wasted_ratio', 'Used memory', $status['memory_usage']['current_wasted_percentage'] / 100);

        // Statistics information.
        $metricsService->gauge('php_opcache_num_cached_scripts_total', '', $status['opcache_statistics']['num_cached_scripts']);
        $metricsService->gauge('php_opcache_num_cached_keys_total', '', $status['opcache_statistics']['num_cached_keys']);
        $metricsService->gauge('php_opcache_max_cached_keys_total', '', $status['opcache_statistics']['max_cached_keys']);
        $metricsService->gauge('php_opcache_hits_total', '', $status['opcache_statistics']['hits']);
        $metricsService->gauge('php_opcache_start_time_duration_seconds', '', $status['opcache_statistics']['start_time']);
        $metricsService->gauge('php_opcache_last_restart_time_duration_seconds', '', $status['opcache_statistics']['last_restart_time']);
        $metricsService->gauge('php_opcache_oom_restarts_total', '', $status['opcache_statistics']['oom_restarts']);
        $metricsService->gauge('php_opcache_hash_restarts_total', '', $status['opcache_statistics']['hash_restarts']);
        $metricsService->gauge('php_opcache_manual_restarts_total', '', $status['opcache_statistics']['manual_restarts']);
        $metricsService->gauge('php_opcache_misses_total', '', $status['opcache_statistics']['misses']);
        $metricsService->gauge('php_opcache_blacklist_misses_total', '', $status['opcache_statistics']['blacklist_misses']);
        $metricsService->gauge('php_opcache_blacklist_miss_ratio_rate', '', $status['opcache_statistics']['blacklist_miss_ratio']);
        $metricsService->gauge('php_opcache_opcache_hit_rate', '', $status['opcache_statistics']['opcache_hit_rate']);
    }

    /**
     * Get APCu statistics.
     *
     * @param MetricsService $metricsService
     *
     * @return void
     */
    private function apcuMetrics(MetricsService $metricsService): void
    {
        $status = apcu_cache_info();

        $metricsService->gauge('php_apcu_num', '', $status['num_slots']);
        $metricsService->gauge('php_apcu_num_hits_total', '', $status['num_hits']);
        $metricsService->gauge('php_apcu_num_misses_total', '', $status['num_misses']);
        $metricsService->gauge('php_apcu_num_inserts_total', '', $status['num_inserts']);
        $metricsService->gauge('php_apcu_num_entries_total', '', $status['num_entries']);
        $metricsService->gauge('php_apcu_expunges_total', '', $status['expunges']);
        $metricsService->gauge('php_apcu_start_time', '', $status['start_time']);
        $metricsService->gauge('php_apcu_mem_size_bytes', '', $status['mem_size']);
    }
}
