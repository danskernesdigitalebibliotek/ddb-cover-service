<?php

namespace App\Tests\Api;

class AuthenticationTest extends AbstractTest
{
    public function testDocsAccess(): void
    {
        $response = static::createClient()->request('GET', $this->apiPath, [
            'headers' => [
                'accept' => 'text/html',
            ],
        ]);
        $this->assertResponseIsSuccessful();
    }

    public function testLoginCovers(): void
    {
        $response = $this->createClientWithCredentials()->request('GET', $this->apiPath.'/covers?type=pid&identifiers=870970-basis%3A29862885,870970-basis%3A27992625&sizes=original,small,medium,large', [
            'headers' => [
                'accept' => 'application/json',
            ],
        ]);
        $this->assertResponseIsSuccessful();
    }

    public function testAccessDenied(): void
    {
        $response = static::createClient()->request('GET', $this->apiPath.'/covers?type=pid&identifiers=870970-basis%3A29862885,870970-basis%3A27992625&sizes=original,small,medium,large', [
            'headers' => [
                'accept' => 'application/json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(401);
    }
}
