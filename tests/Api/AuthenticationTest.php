<?php

namespace App\Tests\Api;

class AuthenticationTest extends AbstractTest
{
    public function testDocsAccess(): void
    {
        static::createClient()->request('GET', $this->apiPath, [
            'headers' => [
                'accept' => 'text/html',
            ],
        ]);
        $this->assertResponseIsSuccessful();
    }

    public function testLoginCovers(): void
    {
        $this->createClientWithCredentials()->request('GET', $this->apiPath.'/covers', [
            'headers' => [
                'accept' => 'application/json',
            ],
            'query' => [
              'type' => 'pid',
              'identifiers' => implode(',', [
                  '870970-basis:29862885',
                  '870970-basis:27992625',
              ]),
              'sizes' => implode(',',['original', 'small', 'medium', 'large']),
            ],
        ]);
        $this->assertResponseIsSuccessful();
    }

    public function testAccessDenied(): void
    {
        static::createClient()->request('GET', $this->apiPath.'/covers', [
            'headers' => [
                'accept' => 'application/json',
            ],
            'query' => [
                'type' => 'pid',
                'identifiers' => implode(',', [
                    '870970-basis:29862885',
                    '870970-basis:27992625',
                ]),
                'sizes' => implode(',',['original', 'small', 'medium', 'large']),
            ],
        ]);
        $this->assertResponseStatusCodeSame(401);
    }
}
