<?php
/**
 * @file
 * Faker class to generate soap response
 */

namespace App\Service\MoreInfoService\Faker;

use App\Service\MoreInfoService\Types\IdentifierInformationType;
use App\Service\MoreInfoService\Types\IdentifierType;
use App\Service\MoreInfoService\Types\ImageType;
use App\Service\MoreInfoService\Types\MoreInfoResponse;
use App\Service\MoreInfoService\Types\RequestStatusType;

class MoreInfoResponseFaker
{
    /**
     * Get a fake response with static data.
     *
     * @return MoreInfoResponse
     */
    public static function getFakeResponse(): MoreInfoResponse
    {
        $requestStatus = new RequestStatusType();
        $requestStatus->statusEnum = 'ok';
        $requestStatus->errorText = '';

        $identifier = new IdentifierType();
        $identifier->pid = '870970-basis:29506914';

        $images = [];

        $image1 = new ImageType();
        $image1->_ = 'https://moreinfo.addi.dk/2.11/more_info_get.php?lokalid=29506914&amp;attachment_type=forside_117&amp;bibliotek=870970&amp;source_id=870970&amp;key=70134739005c7280d86d';
        $image1->imageFormat = 'jpeg';
        $image1->imageSize = 'detail_117';
        $images[] = $image1;

        $image2 = new ImageType();
        $image2->_ = 'https://moreinfo.addi.dk/2.11/more_info_get.php?lokalid=29506914&amp;attachment_type=forside_207&amp;bibliotek=870970&amp;source_id=870970&amp;key=3847f2641a247a88aadd';
        $image2->imageFormat = 'jpeg';
        $image2->imageSize = 'detail_207';
        $images[] = $image2;

        $image3 = new ImageType();
        $image3->_ = 'https://moreinfo.addi.dk/2.11/more_info_get.php?lokalid=29506914&amp;attachment_type=forside_42&amp;bibliotek=870970&amp;source_id=870970&amp;key=cee0124173b0ddc891f3';
        $image3->imageFormat = 'jpeg';
        $image3->imageSize = 'detail_42';
        $images[] = $image3;

        $image4 = new ImageType();
        $image4->_ = 'https://moreinfo.addi.dk/2.11/more_info_get.php?lokalid=29506914&amp;attachment_type=forside_500&amp;bibliotek=870970&amp;source_id=870970&amp;key=59e233adcd3750e1445e';
        $image4->imageFormat = 'jpeg';
        $image4->imageSize = 'detail_500';
        $images[] = $image4;

        $image5 = new ImageType();
        $image5->_ = 'https://moreinfo.addi.dk/2.11/more_info_get.php?lokalid=29506914&amp;attachment_type=forside_lille&amp;bibliotek=870970&amp;source_id=870970&amp;key=62644bff37a95cc6a8a6';
        $image5->imageFormat = 'jpeg';
        $image5->imageSize = 'thumbnail';
        $images[] = $image5;

        $image6 = new ImageType();
        $image6->_ = 'https://moreinfo.addi.dk/2.11/more_info_get.php?lokalid=29506914&amp;attachment_type=forside_stor&amp;bibliotek=870970&amp;source_id=870970&amp;key=5999424f39d3fc3333c0';
        $image6->imageFormat = 'jpeg';
        $image6->imageSize = 'detail';
        $images[] = $image6;

        $identifierInformation = new IdentifierInformationType();
        $identifierInformation->identifierKnown = true;
        $identifierInformation->identifier = $identifier;
        $identifierInformation->coverImage = $images;

        $response = new MoreInfoResponse();
        $response->requestStatus = $requestStatus;
        $response->identifierInformation = $identifierInformation;

        return $response;
    }
}
