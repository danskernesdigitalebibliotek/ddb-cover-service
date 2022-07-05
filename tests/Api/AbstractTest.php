<?php

namespace App\Tests\Api;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Client;
use ApiPlatform\Core\Exception\RuntimeException;
use DanskernesDigitaleBibliotek\AgencyAuthBundle\Security\User;
use Symfony\Component\Cache\Adapter\AdapterInterface;

abstract class AbstractTest extends ApiTestCase
{
    private ?string $token = null;
    private ?Client $clientWithCredentials = null;
    private AdapterInterface $tokenCache;
    protected string $apiPath = '';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        static::bootKernel();

        // Load fixture once pr. test class only to speed up tests.
        $fixture = self::$container->get('App\DataFixtures\AppFixtures');
        $fixture->load();
    }

    public function setUp(): void
    {
        self::bootKernel();
        $this->tokenCache = self::$container->get('token.cache');
        $this->apiPath = self::$container->getParameter('app.path.prefix');
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    protected function findIriBy(string $resourceClass, array $criteria): ?string
    {
        // TODO: Change the autogenerated stub.
        $iri = parent::findIriBy($resourceClass, $criteria);

        if (null === $iri) {
            throw new RuntimeException('No iri found for class: '.$resourceClass);
        }

        return strstr($iri, '/');
    }

    protected function getIdFromIri(string $iri): int
    {
        $pos = \strrpos($iri, '/');

        if (false === $pos) {
            throw new RuntimeException('No / found in iri');
        }

        $id = \substr($iri, $pos + 1);

        return (int) $id;
    }

    protected function createClientWithCredentials(): Client
    {
        if ($this->clientWithCredentials) {
            return $this->clientWithCredentials;
        }

        $this->clientWithCredentials = static::createClient([], [
            'headers' => [
                'authorization' => 'Bearer '.$this->getToken(),
                'accept' => 'application/json',
            ],
        ]);

        return $this->clientWithCredentials;
    }

    protected function getToken(): string
    {
        if ($this->token) {
            return $this->token;
        }

        // Fake client id and token - random generated.
        $token = 'd5db29cd03a2ed055086cef9c31c252b4587d6d0';
        $clientId = '7623cb9a-4573-406a-22ef-f5f8716f07a9';

        $user = new User();
        $user->setPassword($token);
        $user->setExpires(new \DateTime('now + 1 day'));
        $user->setAgency('775100');
        $user->setAuthType('anonymous');
        $user->setClientId($clientId);

        // By caching a valid user under a known token we 'hack' the provider.
        // @see DanskernesDigitaleBibliotek\AgencyAuthBundle\Security\OpenPlatformUserProvider
        $item = $this->tokenCache->getItem($token);
        $item->set($user);
        $this->tokenCache->save($item);

        return $token;
    }
}