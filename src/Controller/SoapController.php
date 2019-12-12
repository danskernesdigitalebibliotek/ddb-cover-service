<?php
/**
 * @file
 * Controller to expose SOAP endpoint that mimics the original 'moreInfo' service.
 */

namespace App\Controller;

use App\Service\MoreInfoService\AbstractMoreInfoService;
use App\Service\MoreInfoService\DbcMoreInfoService;
use App\Service\MoreInfoService\DdbMoreInfoService;
use Psr\Log\LoggerInterface;
use SoapServer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SoapController extends AbstractController
{
    private const DDB_WSDL_FILE = '/src/Service/MoreInfoService/Schemas/DDB/moreInfoService.wsdl';
    private const DBC_WSDL_FILE = '/src/Service/MoreInfoService/Schemas/DBC/moreInfoService.wsdl';

    /**
     * @Route("/2.11/", name="ddb_soap")
     *
     * @param Request $request
     * @param DdbMoreInfoService $ddbMoreInfoService
     * @param LoggerInterface $statsLogger
     * @param $projectDir
     *
     * @return Response
     */
    public function ddbSoap(Request $request, DdbMoreInfoService $ddbMoreInfoService, LoggerInterface $statsLogger, $projectDir): Response
    {
        $dbcWsdlFile = $projectDir.self::DDB_WSDL_FILE;

        return $this->soap($request, $ddbMoreInfoService, $statsLogger, $dbcWsdlFile);
    }

    /**
     * @Route("/fbs/2.11/", name="fbs_soap")
     *
     * @param Request $request
     * @param DbcMoreInfoService $dbcMoreInfoService
     * @param LoggerInterface $statsLogger
     * @param $projectDir
     *
     * @return Response
     */
    public function fbsSoap(Request $request, DbcMoreInfoService $dbcMoreInfoService, LoggerInterface $statsLogger, $projectDir): Response
    {
        $dbcWsdlFile = $projectDir.self::DBC_WSDL_FILE;

        return $this->soap($request, $dbcMoreInfoService, $statsLogger, $dbcWsdlFile);
    }

    /**
     * Return a SOAP response for the given request.
     *
     * @param Request $request
     * @param AbstractMoreInfoService $dbcMoreInfoService
     * @param LoggerInterface $statsLogger
     * @param $wsdlFile
     *
     * @return Response
     */
    private function soap(Request $request, AbstractMoreInfoService $dbcMoreInfoService, LoggerInterface $statsLogger, $wsdlFile): Response
    {
        $soapServer = new SoapServer($wsdlFile, ['cache_wsdl' => WSDL_CACHE_NONE]);
        $soapServer->setObject($dbcMoreInfoService);

        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml; charset=ISO-8859-1');

        ob_start();
        try {
            $soapServer->handle();
        } catch (\Exception $exception) {
            $statsLogger->error('SOAP endpoint exception', [
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
