<?php

/**
 * @file
 * Behat context for authentication.
 */

namespace App\Tests\Behat;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behatch\Context\RestContext;
use DanskernesDigitaleBibliotek\AgencyAuthBundle\Security\User;
use Faker\Factory;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\AdapterInterface;

/**
 * Class AuthContext.
 */
class AuthContext implements Context
{
    private $tokenCache;
    private $restContext;

    /**
     * AuthContext constructor.
     */
    public function __construct(AdapterInterface $tokenCache)
    {
        $this->tokenCache = $tokenCache;
    }

    /**
     * @BeforeScenario @login
     *
     * @see https://symfony.com/doc/current/security/entity_provider.html#creating-your-first-user
     *
     * @throws InvalidArgumentException
     */
    public function login(BeforeScenarioScope $scope)
    {
        $faker = Factory::create();

        $token = $faker->sha1;
        $clientId = $faker->uuid;

        $user = new User();
        $user->setPassword($token);
        $user->setExpires(new \DateTime('now + 1 day'));
        $user->setAgency('775100');
        $user->setAuthType('anonymous');
        $user->setClientId($clientId);

        // Store access token in local cache.
        $item = $this->tokenCache->getItem($token);
        $item->set($user);
        $this->tokenCache->save($item);

        $this->restContext = $scope->getEnvironment()->getContext(RestContext::class);
        $this->restContext->iAddHeaderEqualTo('Authorization', "Bearer $token");
    }

    /**
     * @AfterScenario @logout
     */
    public function logout()
    {
        if ($this->restContext) {
            $this->restContext->iAddHeaderEqualTo('Authorization', '');
        }
    }
}
