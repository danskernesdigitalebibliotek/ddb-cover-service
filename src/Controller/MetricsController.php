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
        return new Response($metricsService->render(), Response::HTTP_OK, ['content-type' => 'text/plain']);
    }
}
