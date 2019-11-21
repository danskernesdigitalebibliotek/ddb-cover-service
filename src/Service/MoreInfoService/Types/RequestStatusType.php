<?php

/**
 * This class was created using wsdl2php.
 *
 * @wsdl2php  Wed, 21 Nov 2018 13:11:08 +0100 - Last modified
 * @WSDL      moreinfo.wsdl
 * @Processed Tue, 20 Nov 2018 20:44:22 +0100
 * @Hash      6b367f18303ed027092028ce1da3cfd4
 */

namespace App\Service\MoreInfoService\Types;

class RequestStatusType
{
    /**
     * @basetype statusEnum
     *
     * @var string
     */
    public $statusEnum;

    /**
     * @var string
     */
    public $errorText;
}
