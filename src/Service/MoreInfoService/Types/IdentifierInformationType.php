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
 * @Hash      e517e374168b5f7976b1eb3a7f021ea2
 */

namespace App\Service\MoreInfoService\Types;

class IdentifierInformationType
{
    /**
     * @basetype boolean
     *
     * @var      bool
     */
    public $identifierKnown;

    /**
     * @var MoreInfoService_IdentifierType
     */
    public $identifier;

    /**
     * @var MoreInfoService_ImageType
     */
    public $coverImage;

    /**
     * @var MoreInfoService_FormatType
     */
    public $coverText;

    /**
     * @var MoreInfoService_FormatType
     */
    public $colophon;

    /**
     * @var MoreInfoService_FormatType
     */
    public $titlePage;

    /**
     * @var MoreInfoService_FormatType
     */
    public $tableOfContents;

    /**
     * @var MoreInfoService_FormatType
     */
    public $backPage;

    /**
     * @var MoreInfoService_ImageType
     */
    public $backImage;

    /**
     * @var MoreInfoService_FormatType
     */
    public $netArchive;

    /**
     * @var MoreInfoService_FormatType
     */
    public $externUrl;
}
