<?php

namespace App\Service\MoreInfoService;

class DefaultCoverMoreInfoService extends AbstractMoreInfoService
{
    private const SERVICE_NAMESPACE = 'http://oss.dbc.dk/ns/moreinfo';
    private const WSDL = __DIR__.'/Schemas/DBC/moreInfoService.wsdl';
    private const PROVIDE_DEFAULT_COVER = true;

    protected function getNameSpace(): string
    {
        return self::SERVICE_NAMESPACE;
    }

    protected function getWsdl(): string
    {
        return self::WSDL;
    }

    protected function provideDefaultCover(): bool
    {
        return self::PROVIDE_DEFAULT_COVER;
    }
}
