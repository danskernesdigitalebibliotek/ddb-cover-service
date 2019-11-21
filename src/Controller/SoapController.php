<?php
/**
 * @file
 * Controller to expose SOAP endpoint that mimics the original 'moreInfo' service.
 */

namespace App\Controller;

use App\Service\MoreInfoService\MoreInfoService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SoapController extends AbstractController
{
    /**
     * @Route("/2.11/", name="soap_trailing_slash")
     */
    public function soap(Request $request, MoreInfoService $moreInfoService, LoggerInterface $statsLogger, $projectDir)
    {
        $soapServer = new \SoapServer($projectDir.'/src/Service/MoreInfoService/Schemas/moreInfoService.wsdl', ['cache_wsdl' => WSDL_CACHE_NONE]);
        $soapServer->setObject($moreInfoService);

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

    /**
     * @Route("/fbs/2.11/", name="fbs_soap")
     */
    public function fbsSoap(Request $request, MoreInfoService $moreInfoService, LoggerInterface $statsLogger, $projectDir)
    {
        $soapServer = new \SoapServer($projectDir.'/src/Service/MoreInfoService/Schemas/DBC/moreInfoService.wsdl', ['cache_wsdl' => WSDL_CACHE_NONE]);
        $soapServer->setObject($moreInfoService);

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
