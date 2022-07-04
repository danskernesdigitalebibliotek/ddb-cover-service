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
        $response = $this->createClientWithCredentials()->request('GET', $this->apiPath.'/covers', [
            'headers' => [
                'accept' => 'application/json',
            ],
            'query' => [
                'type' => 'pid',
                'identifiers' => implode(',', [
                    '870970-basis:29862885',
                    '870970-basis:27992625',
                ]),
                'sizes' => implode(',', ['original', 'small', 'medium', 'large']),
            ],
        ]);
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
                        'url' => 'https://res.cloudinary.com/dandigbib/image/upload/v1543609481/bogportalen.dk/9792427891279.jpg',
                        'format' => 'png',
                        'size' => 'original',
                    ],
                    'small' => [
                        'url' => 'https://res.cloudinary.com/dandigbib/image/upload/t_ddb_cover_small/v1543609481/bogportalen.dk/9792427891279.jpg',
                        'format' => 'jpeg',
                        'size' => 'small',
                    ],
                    'medium' => [
                        'url' => 'https://res.cloudinary.com/dandigbib/image/upload/t_ddb_cover_medium/v1543609481/bogportalen.dk/9792427891279.jpg',
                        'format' => 'jpeg',
                        'size' => 'medium',
                    ],
                    'large' => [
                        'url' => null,
                        'format' => 'jpeg',
                        'size' => 'large',
                    ],
                ],
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
                    ],
                ],
            ],
        ]);
        $this->assertCount(2, $json);
    }

    public function testGetOneRealCovers(): void
    {
        $response = $this->createClientWithCredentials()->request('GET', $this->apiPath.'/covers', [
            'headers' => [
                'accept' => 'application/json',
            ],
            'query' => [
                'type' => 'pid',
                'identifiers' => implode(',', [
                    '870970-basis:29862885',
                    '870970-basis:11111111',
                ]),
                'sizes' => implode(',', ['original', 'small', 'medium', 'large']),
            ],
        ]);
        $body = $response->getContent();
        $json = \json_decode($body, false, 512, JSON_THROW_ON_ERROR);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json; charset=utf-8');
        $this->assertJsonContains([
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
                    ],
                ],
            ],
        ]);
        $this->assertCount(1, $json);
    }

    public function testGetUnknownCovers(): void
    {
        $response = $this->createClientWithCredentials()->request('GET', $this->apiPath.'/covers', [
            'headers' => [
                'accept' => 'application/json',
            ],
            'query' => [
                'type' => 'pid',
                'identifiers' => implode(',', [
                    '870970-basis:11111111',
                ]),
                'sizes' => implode(',', ['original', 'small', 'medium', 'large']),
            ],
        ]);
        $body = $response->getContent();
        $json = \json_decode($body, false, 512, JSON_THROW_ON_ERROR);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json; charset=utf-8');
        $this->assertJsonContains([]);
        $this->assertCount(0, $json);
    }

    /**
     * I should get a 400 bad request if I send to many identifiers.
     */
    public function testToManyIdentifiers(): void
    {
        $this->createClientWithCredentials()->request('GET', $this->apiPath.'/covers', [
            'headers' => [
                'accept' => 'application/json',
            ],
            'query' => [
                'type' => 'isbn',
                'identifiers' => implode(',', [
                    '9780119135640',
                    '9799913633580',
                    '9792806497771',
                    '9781351129428',
                    '9798058560423',
                    '9789318143272',
                ]),
                'sizes' => implode(',', ['original', 'small', 'medium', 'large']),
            ],
        ]);
        $this->assertResponseStatusCodeSame(400);
    }

    /**
     * I should get a 400 bad request if I send an empty sizes parameter.
     */
    public function testEmptySizeQuery(): void
    {
        $this->createClientWithCredentials()->request('GET', $this->apiPath.'/covers', [
            'headers' => [
                'accept' => 'application/json',
            ],
            'query' => [
                'type' => 'isbn',
                'identifiers' => implode(',', [
                    '9780119135640',
                    '9799913633580',
                ]),
                'sizes' => '',
            ],
        ]);
        $this->assertResponseStatusCodeSame(400);
    }

    /**
     * No Size should return default.
     */
    public function testRequestNoSize(): void
    {
        $response = $this->createClientWithCredentials()->request('GET', $this->apiPath.'/covers', [
            'headers' => [
                'accept' => 'application/json',
            ],
            'query' => [
                'type' => 'pid',
                'identifiers' => implode(',', [
                    '870970-basis:29862885',
                    '870970-basis:27992625',
                ]),
            ],
        ]);
        $body = $response->getContent();
        $json = \json_decode($body, false, 512, JSON_THROW_ON_ERROR);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json; charset=utf-8');
        $this->assertJsonContains([
            [
                'id' => '870970-basis:27992625',
                'type' => 'pid',
                'imageUrls' => [
                    'default' => [
                        'url' => null,
                        'format' => 'jpeg',
                        'size' => 'default',
                    ],
                ],
            ],
            [
                'id' => '870970-basis:29862885',
                'type' => 'pid',
                'imageUrls' => [
                    'default' => [
                        'url' => 'https://res.cloudinary.com/dandigbib/image/upload/t_ddb_cover/v1543609481/bogportalen.dk/9785341366046.jpg',
                        'format' => 'jpeg',
                        'size' => 'default',
                    ],
                ],
            ],
        ]);
        $this->assertCount(2, $json);
    }

    /**
     * I should get a 400 bad request if I request unknown sizes.
     */
    public function testRequestUnknownSize(): void
    {
        $this->createClientWithCredentials()->request('GET', $this->apiPath.'/covers', [
            'headers' => [
                'accept' => 'application/json',
            ],
            'query' => [
                'type' => 'isbn',
                'identifiers' => implode(',', [
                    '9780119135640',
                    '9799913633580',
                ]),
                'sizes' => 'Original, mega, huge',
            ],
        ]);
        $this->assertResponseStatusCodeSame(400);
    }

    /**
     * I should get a 200 ok if I request known size.
     */
    public function testKnownSize(): void
    {
        $response = $this->createClientWithCredentials()->request('GET', $this->apiPath.'/covers', [
            'headers' => [
                'accept' => 'application/json',
            ],
            'query' => [
                'type' => 'isbn',
                'identifiers' => implode(',', [
                    '9785341366046',
                    '9799913633580',
                ]),
                'sizes' => 'original',
            ],
        ]);
        $this->assertResponseStatusCodeSame(200);
        $body = $response->getContent();
        $json = \json_decode($body, false, 512, JSON_THROW_ON_ERROR);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json; charset=utf-8');
        $this->assertJsonContains([]);
        $this->assertCount(2, $json);
    }

    /**
     * I should get a 200 ok if I request known size with wrong case.
     */
    public function testKnownSizeWrongCase(): void
    {
        $response = $this->createClientWithCredentials()->request('GET', $this->apiPath.'/covers', [
            'headers' => [
                'accept' => 'application/json',
            ],
            'query' => [
                'type' => 'isbn',
                'identifiers' => implode(',', [
                    '9785341366046',
                    '9799913633580',
                ]),
                'sizes' => 'OrigInal',
            ],
        ]);
        $this->assertResponseStatusCodeSame(200);
        $body = $response->getContent();
        $json = \json_decode($body, false, 512, JSON_THROW_ON_ERROR);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json; charset=utf-8');
        $this->assertJsonContains([]);
        $this->assertCount(2, $json);
    }

    /**
     * I should get a 200 ok if I request known sizes.
     */
    public function testGetSmallMediumCovers(): void
    {
        $response = $this->createClientWithCredentials()->request('GET', $this->apiPath.'/covers', [
            'headers' => [
                'accept' => 'application/json',
            ],
            'query' => [
                'type' => 'pid',
                'identifiers' => implode(',', [
                    '870970-basis:29862885',
                    '870970-basis:27992625',
                ]),
                'sizes' => implode(',', ['small', 'medium']),
            ],
        ]);
        $body = $response->getContent();
        $json = \json_decode($body, false, 512, JSON_THROW_ON_ERROR);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json; charset=utf-8');
        $this->assertJsonContains([
            [
                'id' => '870970-basis:27992625',
                'type' => 'pid',
                'imageUrls' => [
                    'small' => [
                        'url' => 'https://res.cloudinary.com/dandigbib/image/upload/t_ddb_cover_small/v1543609481/bogportalen.dk/9792427891279.jpg',
                        'format' => 'jpeg',
                        'size' => 'small',
                    ],
                    'medium' => [
                        'url' => 'https://res.cloudinary.com/dandigbib/image/upload/t_ddb_cover_medium/v1543609481/bogportalen.dk/9792427891279.jpg',
                        'format' => 'jpeg',
                        'size' => 'medium',
                    ],
                ],
            ],
            [
                'id' => '870970-basis:29862885',
                'type' => 'pid',
                'imageUrls' => [
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
                ],
            ],
        ]);
        $this->assertCount(2, $json);
    }
}
