<?php

/**
 * @file
 */

namespace App\Exception;

class XmlReaderException extends \Exception
{
    /**
     * {@inheritdoc}
     */
    public function __construct($message = 'You must call open() before calling other functions.', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
