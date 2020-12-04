<?php

/**
 * @file
 * Logger processor adding
 */

namespace App\Logger;

/**
 * Class RequestIdProcessor.
 */
class RequestIdProcessor
{
    private $requestId;

    /**
     * RequestIdProcessor constructor.
     *
     * @param string $bindRequestId
     */
    public function __construct(string $bindRequestId)
    {
        $this->requestId = $bindRequestId;
    }

    /**
     * Magic invoke function.
     *
     * @param array $record
     *   Log record
     *
     * @return array
     *   The record added require id in extras
     */
    public function __invoke(array $record)
    {
        $record['extra']['requestId'] = $this->requestId;

        return $record;
    }
}
