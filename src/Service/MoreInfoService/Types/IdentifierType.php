<?php

/**
 * This class was created using wsdl2php.
 *
 * @wsdl2php  Wed, 21 Nov 2018 13:11:08 +0100 - Last modified
 * @WSDL      moreinfo.wsdl
 * @Processed Tue, 20 Nov 2018 20:44:22 +0100
 * @Hash      843ce1cf6de6480795673dbd8261644a
 */

namespace App\Service\MoreInfoService\Types;

class IdentifierType
{
    /**
     * @basetype FaustType
     *
     * @var      string
     */
    public $faust;

    /**
     * @basetype IsbnType
     *
     * @var      string
     */
    public $isbn;

    /**
     * @basetype PidType
     *
     * @var      string
     */
    public $pid;

    /**
     * @basetype PidListType
     *
     * @var      string
     */
    public $pidList;

    /**
     * @basetype LocalIdentifierType
     *
     * @var      string
     */
    public $localIdentifier;

    /**
     * @basetype LibraryCodeType
     *
     * @var      string
     */
    public $libraryCode;
}
