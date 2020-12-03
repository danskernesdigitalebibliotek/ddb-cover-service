<?php

namespace App\Logger;

class RequestIdProcessor
{
    private $requestId;

    public function __construct(String $bindRequestId)
    {
        $this->requestId = $bindRequestId;
    }

    public function __invoke(array $record)
    {
        $record['extra']['requestId'] = $this->requestId;
        return $record;
    }
}