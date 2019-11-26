<?php


namespace App\Service\MoreInfoService;


class DdbMoreInfoService extends AbstractMoreInfoService
{
    private const SERVICE_NAMESPACE = 'https://cover.dandigbib.org/ns/moreinfo_wsdl';
    private const WSDL = __DIR__.'/Schemas/DDB/moreInfoService.wsdl';


    protected function getNameSpace(): string
    {
        return self::SERVICE_NAMESPACE;
    }

    protected function getWsdl(): string
    {
        return self::WSDL;
    }
}
