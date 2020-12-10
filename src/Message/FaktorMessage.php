<?php


namespace App\Message;


class FaktorMessage
{
    private $clientID;
    private $remoteIP;
    private $isType;
    private $isIdentifiers;
    private $fileNames;
    private $matches;
    private $traceId;

    /**
     * @return string
     */
    public function getClientID(): string
    {
        return $this->clientID;
    }

    /**
     * @param string $clientID
     *
     * @return FaktorMessage
     */
    public function setClientID(string $clientID): self
    {
        $this->clientID = $clientID;

        return $this;
    }

    /**
     * @return string
     */
    public function getRemoteIP(): string
    {
        return $this->remoteIP;
    }

    /**
     * @param string $remoteIP
     *
     * @return FaktorMessage
     */
    public function setRemoteIP(string $remoteIP): self
    {
        $this->remoteIP = $remoteIP;

        return $this;
    }

    /**
     * @return string
     */
    public function getIsType(): string
    {
        return $this->isType;
    }

    /**
     * @param string $isType
     *
     * @return FaktorMessage
     */
    public function setIsType(string $isType): self
    {
        $this->isType = $isType;

        return $this;
    }

    /**
     * @return array
     */
    public function getIsIdentifiers(): array
    {
        return $this->isIdentifiers;
    }

    /**
     * @param array $isIdentifiers
     *
     * @return FaktorMessage
     */
    public function setIsIdentifiers(array $isIdentifiers): self
    {
        $this->isIdentifiers = $isIdentifiers;

        return $this;
    }

    /**
     * @return array
     */
    public function getFileNames()
    {
        return $this->fileNames;
    }

    /**
     * @param array $fileNames
     *
     * @return FaktorMessage
     */
    public function setFileNames(array $fileNames): self
    {
        $this->fileNames = $fileNames;

        return $this;
    }

    /**
     * @return array
     */
    public function getMatches(): array
    {
        return $this->matches;
    }

    /**
     * @param array $matches
     *
     * @return FaktorMessage
     */
    public function setMatches(array $matches): self
    {
        $this->matches = $matches;

        return $this;
    }

    /**
     * Get request id (which is unique for the whole request).
     *
     * @return string
     *   The request id
     */
    public function getTraceId(): string
    {
        return $this->traceId;
    }

    /**
     * Set trace id (which is unique for the whole request).
     *
     * @param string $traceId
     *   The trace id used to trace this message between services.
     *
     * @return FaktorMessage
     */
    public function setTraceId(string $traceId): self
    {
        $this->traceId = $traceId;

        return $this;
    }
}