<?php
/**
 * @file
 * Controller to expose SOAP endpoint that mimics the original 'moreInfo' service.
 */

namespace App\Controller;

use App\Service\MetricsService;
use App\Service\MoreInfoService\AbstractMoreInfoService;
use App\Service\MoreInfoService\DbcMoreInfoService;
use App\Service\MoreInfoService\DdbMoreInfoService;
use App\Service\MoreInfoService\DefaultCoverMoreInfoService;
use App\Service\StatsLoggingService;
use Psr\Log\LoggerInterface;
use SoapServer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SoapController extends AbstractController
{
    private const DDB_WSDL_FILE = '/src/Service/MoreInfoService/Schemas/DDB/moreInfoService.wsdl';
    private const DBC_WSDL_FILE = '/src/Service/MoreInfoService/Schemas/DBC/moreInfoService.wsdl';

    private $dispatcher;

    /**
     * SoapController constructor.
     *
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @Route("/2.11/", name="ddb_soap")
     *
     * @param Request $request
     * @param DdbMoreInfoService $ddbMoreInfoService
     * @param StatsLoggingService $statsLoggingService
     *    Statistics logging service
     * @param MetricsService $metricsService
     * @param $projectDir
     *
     * @return Response
     */
    public function ddbSoap(Request $request, DdbMoreInfoService $ddbMoreInfoService, StatsLoggingService $statsLoggingService, MetricsService $metricsService, $projectDir): Response
    {
        $labels = ['type' => 'ddbSoap'];
        $metricsService->counter('soap_requests_total', 'Total soap requests', 1, $labels);

        $dbcWsdlFile = $projectDir.self::DDB_WSDL_FILE;

        return $this->soap($request, $ddbMoreInfoService, $statsLoggingService, $dbcWsdlFile);
    }

    /**
     * @Route("/fbs/2.11/", name="fbs_soap")
     *
     * @param Request $request
     * @param DbcMoreInfoService $dbcMoreInfoService
     * @param StatsLoggingService $statsLoggingService
     *    Statistics logging service
     * @param MetricsService $metricsService
     * @param $projectDir
     *
     * @return Response
     */
    public function fbsSoap(Request $request, DbcMoreInfoService $dbcMoreInfoService, StatsLoggingService $statsLoggingService, MetricsService $metricsService, $projectDir): Response
    {
        $labels = ['type' => 'fbsSoap'];
        $metricsService->counter('soap_requests_total', 'Total soap requests', 1, $labels);

        $dbcWsdlFile = $projectDir.self::DBC_WSDL_FILE;

        return $this->soap($request, $dbcMoreInfoService, $statsLoggingService, $dbcWsdlFile);
    }

    /**
     * @Route("/defaultcover/2.11/", name="default_cover_soap")
     *
     * @param Request $request
     * @param DefaultCoverMoreInfoService $defaultCoverMoreInfoService
     * @param StatsLoggingService $statsLoggingService
     * @param MetricsService $metricsService
     * @param $projectDir
     *
     * @return Response
     */
    public function defaultCoverSoap(Request $request, DefaultCoverMoreInfoService $defaultCoverMoreInfoService, StatsLoggingService $statsLoggingService, MetricsService $metricsService, $projectDir): Response
    {
        $labels = ['type' => 'defaultCoverSoap'];
        $metricsService->counter('soap_requests_total', 'Total soap requests', 1, $labels);

        $dbcWsdlFile = $projectDir.self::DBC_WSDL_FILE;

        return $this->soap($request, $defaultCoverMoreInfoService, $statsLoggingService, $dbcWsdlFile);
    }

    /**
     * Return a SOAP response for the given request.
     *
     * @param Request $request
     * @param AbstractMoreInfoService $moreInfoService
     * @param LoggerInterface $statsLoggingService
     * @param $wsdlFile
     *
     * @return Response
     */
    private function soap(Request $request, AbstractMoreInfoService $moreInfoService, LoggerInterface $statsLoggingService, $wsdlFile): Response
    {
        $soapServer = new SoapServer($wsdlFile, ['cache_wsdl' => WSDL_CACHE_MEMORY]);
        $soapServer->setObject($moreInfoService);

        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml; charset=ISO-8859-1');

        ob_start();
        try {
            $soapServer->handle();
        } catch (\Exception $exception) {
            $statsLoggingService->error('SOAP endpoint exception', [
                'service' => 'SoapController',
                'remoteIP' => $request->getClientIp(),
                'message' => $exception->getMessage(),
            ]);

            $soapServer->fault('Sender', $exception->getMessage());
        }
        $response->setContent(ob_get_clean());

        return $response;
    }
}
