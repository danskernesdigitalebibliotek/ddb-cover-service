<?php
/**
 * @file
 * User with information obtained during authentication.
 */

namespace App\Security;

use DateTime;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class User.
 */
class User implements UserInterface
{
    private $password;
    private $expires;
    private $agency;
    private $authType;
    private $clientId;

    /**
     * Get this users "password" expire date.
     *
     * @return DateTime|null
     */
    public function getExpires(): ?DateTime
    {
        return $this->expires;
    }

    /**
     * Set this users "password" expire date.
     *
     * @param mixed $expires
     */
    public function setExpires(DateTime $expires): void
    {
        $this->expires = $expires;
    }

    /**
     * Get the users agency.
     *
     * @return string|null
     */
    public function getAgency(): ?string
    {
        return $this->agency;
    }

    /**
     * Set the users agency.
     *
     * @param string $agency
     */
    public function setAgency($agency): void
    {
        $this->agency = $agency;
    }

    /**
     * Get users authentication type.
     *
     * @return mixed
     */
    public function getAuthType(): string
    {
        return $this->authType;
    }

    /**
     * Set users authentication type.
     *
     * @param mixed $authType
     */
    public function setAuthType($authType): void
    {
        $this->authType = $authType;
    }

    /**
     * Get users client id.
     *
     * @return mixed
     */
    public function getClientId(): string
    {
        return $this->clientId;
    }

    /**
     * Set users client id.
     *
     * @param mixed $clientId
     */
    public function setClientId($clientId): void
    {
        $this->clientId = $clientId;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoles(): array
    {
        return ['ROLE_COVER_READ'];
    }

    /**
     * {@inheritdoc}
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword($password): void
    {
        $this->password = $password;
    }

    /**
     * {@inheritdoc}
     */
    public function getSalt(): ?string
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getUsername(): string
    {
        return $this->agency;
    }

    /**
     * {@inheritdoc}
     */
    public function eraseCredentials(): ?string
    {
        return null;
    }
}
