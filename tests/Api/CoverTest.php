<?php

namespace App\Tests\Api;

use App\DataFixtures\AppFixtures;

class CoverTest extends AbstractTest
{
    public function setUp(): void
    {
        parent::setUp();

        /** @var AppFixtures $fixture */
        $fixture = self::$container->get('App\DataFixtures\AppFixtures');
        $fixture->load();
    }

    public function testGetCovers(): void
    {
        $response = $this->createClientWithCredentials()->request('GET', $this->apiPath.'/covers?type=pid&identifiers=870970-basis%3A29862885,870970-basis%3A27992625&sizes=original,small,medium,large');
        $body = $response->getContent();
        $json = \json_decode($body, false, 512, JSON_THROW_ON_ERROR);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json; charset=utf-8');
        $this->assertJsonContains([
            [
                'id' => '870970-basis:27992625',
                'type' => 'pid',
                'imageUrls' => [
                    'original' => [
                        'url' => 'https://res.cloudinary.com/dandigbib/image/upload/v1543609481/bogportalen.dk/9782821623682.jpg',
                        'format' => 'png',
                        'size' => 'original',
                    ],
                    'small' => [
                        'url' => 'https://res.cloudinary.com/dandigbib/image/upload/t_ddb_cover_small/v1543609481/bogportalen.dk/9782821623682.jpg',
                        'format' => 'jpeg',
                        'size' => 'small',
                    ],
                    'medium' => [
                        'url' => 'https://res.cloudinary.com/dandigbib/image/upload/t_ddb_cover_medium/v1543609481/bogportalen.dk/9782821623682.jpg',
                        'format' => 'jpeg',
                        'size' => 'medium',
                    ],
                    'large' => [
                        'url' => NULL,
                        'format' => 'jpeg',
                        'size' => 'large',
                    ]
                ]
            ],
            [
                'id' => '870970-basis:29862885',
                'type' => 'pid',
                'imageUrls' => [
                    'original' => [
                        'url' => 'https://res.cloudinary.com/dandigbib/image/upload/v1543609481/bogportalen.dk/9785341366046.jpg',
                        'format' => 'jpeg',
                        'size' => 'original',
                    ],
                    'small' => [
                        'url' => 'https://res.cloudinary.com/dandigbib/image/upload/t_ddb_cover_small/v1543609481/bogportalen.dk/9785341366046.jpg',
                        'format' => 'jpeg',
                        'size' => 'small',
                    ],
                    'medium' => [
                        'url' => 'https://res.cloudinary.com/dandigbib/image/upload/t_ddb_cover_medium/v1543609481/bogportalen.dk/9785341366046.jpg',
                        'format' => 'jpeg',
                        'size' => 'medium',
                    ],
                    'large' => [
                        'url' => 'https://res.cloudinary.com/dandigbib/image/upload/t_ddb_cover_large/v1543609481/bogportalen.dk/9785341366046.jpg',
                        'format' => 'jpeg',
                        'size' => 'large',
                    ]
                ]
            ]
        ]);
        $this->assertCount(2, $json);
    }

    public function testGetUnknownCovers(): void
    {
        $response = $this->createClientWithCredentials()->request('GET', $this->apiPath.'/covers?type=pid&identifiers=870970-basis%3A11111111&sizes=original,small,medium,large');
        $body = $response->getContent();
        $json = \json_decode($body, false, 512, JSON_THROW_ON_ERROR);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json; charset=utf-8');
        $this->assertJsonContains([]);
        $this->assertCount(0, $json);
    }

//    # APP_API_MAX_IDENTIFIERS is 5 for .env.test
//    @login
//    Scenario: I should get a 400 bad request if I send to many identifiers
//    Given I add "Accept" header equal to "application/json"
//    And I send a "GET" request to "/api/v2/covers" with parameters:
//    | key         | value                                                                               |
//    | identifiers | 9780119135640,9799913633580,9792806497771,9781351129428,9798058560423,9789318143272 |
//    | type        | isbn                                                                                |
//    Then the response status code should be 400
//    And the response should be in JSON
//    And the JSON node "root.title" should be equal to "An error occurred"
//    And the JSON node "root.detail" should be equal to "Maximum identifiers per request exceeded. 5 allowed. 6 received."
//
//    @login
//    Scenario: I should get a 400 bad request if I send an empty sizes parameter
//    Given I add "Accept" header equal to "application/json"
//    And I send a "GET" request to "/api/v2/covers" with parameters:
//    | key         | value                       |
//    | identifiers | 9780119135640,9799913633580 |
//    | type        | isbn                        |
//    | sizes       |                             |
//    Then the response status code should be 400
//    And the response should be in JSON
//    And the JSON node "root.title" should be equal to "An error occurred"
//    And the JSON node "root.detail" should be equal to 'The "sizes" parameter cannot be empty. Either omit the parameter or submit a list of valid image sizes.'
//
//    @login
//    Scenario: I should get a 400 bad request if I request unknown sizes
//    Given I add "Accept" header equal to "application/json"
//    And I send a "GET" request to "/api/v2/covers" with parameters:
//    | key         | value                       |
//    | identifiers | 9780119135640,9799913633580 |
//    | type        | isbn                        |
//    | sizes       | Original, mega, huge        |
//    Then the response status code should be 400
//    And the JSON node "root.title" should be equal to "An error occurred"
//    And the JSON node "root.detail" should be equal to "Unknown images size(s): mega, huge - Valid sizes are original, default, small, medium, large"
//
//    @login
//    Scenario: I should get a 200 ok if I request known size
//    Given I add "Accept" header equal to "application/json"
//    And I send a "GET" request to "/api/v2/covers" with parameters:
//    | key         | value                       |
//    | identifiers | 9780119135640,9799913633580 |
//    | type        | isbn                        |
//    | sizes       | original                    |
//    Then the response status code should be 200
//
//    @login
//    Scenario: I should get a 200 ok if I request known size with wrong case
//    Given I add "Accept" header equal to "application/json"
//    And I send a "GET" request to "/api/v2/covers" with parameters:
//      | key         | value                       |
//      | identifiers | 9780119135640,9799913633580 |
//      | type        | isbn                        |
//      | sizes       | Original                    |
//    Then the response status code should be 200
//
//    @login
//    Scenario: I should get a 200 ok if I request known sizes
//    Given I add "Accept" header equal to "application/json"
//    And I send a "GET" request to "/api/v2/covers" with parameters:
//      | key         | value                       |
//      | identifiers | 9780119135640,9799913633580 |
//      | type        | isbn                        |
//      | sizes       | original, large             |
//    Then the response status code should be 200

}
