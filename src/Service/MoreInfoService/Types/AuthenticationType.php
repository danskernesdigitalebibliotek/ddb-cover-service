<?php

/**
 * This class was created using wsdl2php.
 *
 * @wsdl2php  Wed, 21 Nov 2018 13:11:08 +0100 - Last modified
 * @WSDL      moreinfo.wsdl
 * @Processed Tue, 20 Nov 2018 20:44:22 +0100
 * @Hash      355d4625a4ff14787986ad86a1bf8e4d
 */

namespace App\Service\MoreInfoService\Types;

class AuthenticationType
{
    /**
     * @basetype AuthenticationUserType
     *
     * @var      string
     */
    public $authenticationUser;

    /**
     * @basetype AuthenticationGroupType
     *
     * @var      string
     */
    public $authenticationGroup;

    /**
     * @basetype AuthenticationPasswordType
     *
     * @var      string
     */
    public $authenticationPassword;
}
