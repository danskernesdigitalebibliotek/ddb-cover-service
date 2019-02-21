<?php

/**
 * @file
 */

namespace App\Exception;

use Throwable;

/**
 * Class MaterialTypeException.
 */
class MaterialTypeException extends \Exception
{
    private $materialType;

    public function __construct(string $message = '', int $code = 0, Throwable $previous = null, $materialType = 'Unknown')
    {
        parent::__construct($message, $code, $previous);

        $this->materialType = $materialType;
    }

    /**
     * @param string $materialType
     */
    public function setMaterialType(string $materialType): void
    {
        $this->materialType = $materialType;
    }

    /**
     * @return string
     */
    public function getMaterialType()
    {
        return $this->materialType;
    }
}
