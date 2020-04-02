<?php

/**
 * This class was created using wsdl2php.
 *
 * @wsdl2php  Wed, 21 Nov 2018 13:11:08 +0100 - Last modified
 *
 * @WSDL      moreinfo.wsdl
 *
 * @Processed Tue, 20 Nov 2018 20:44:22 +0100
 *
 * @Hash      070016b594a8df7403fa0445cb7e2b63
 */

namespace App\Service\MoreInfoService\Types;

class MoreInfoRequest
{
    /**
     * @var MoreInfoService_AuthenticationType
     */
    public $authentication;

    /**
     * @var MoreInfoService_IdentifierType
     */
    public $identifier;

    /**
     * @basetype outputTypeType
     *
     * @var      string
     */
    public $outputType;

    /**
     * @var string
     */
    public $callback;

    /**
     * @var string
     */
    public $trackingId;
}
