<?php
/**
 * @file
 * Data model the identifiers found for a material in the open platform.
 */

namespace App\Utils\OpenPlatform;

use App\Exception\MaterialTypeException;
use App\Utils\Types\IdentifierType;

/**
 * Class MaterialIdentifier.
 */
class MaterialIdentifier
{
    private $type;
    private $id;

    // The valid IS types in the data well.
    private $types = [];

    /**
     * MaterialIdentifier constructor.
     *
     * @param string $type
     *   The material type
     * @param string $id
     *   The identifier for this material
     *
     * @throws MaterialTypeException
     * @throws \ReflectionException
     */
    public function __construct(string $type, string $id)
    {
        // Build types array.
        $obj = new \ReflectionClass(IdentifierType::class);
        $this->types = array_values($obj->getConstants());

        // Validate type.
        if (!in_array($type, $this->types)) {
            throw new MaterialTypeException('Unknown material type: '.$type, 0, null, $type);
        }

        $this->type = $type;
        $this->id = $id;
    }

    /**
     * Get the identifier.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get the type of identifier.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }
}
