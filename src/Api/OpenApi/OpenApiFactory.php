<?php
/**
 * @file
 * OpenApi Service Decorator
 *
 * @see https://api-platform.com/docs/core/openapi/#overriding-the-openapi-specification
 */

namespace App\Api\OpenApi;

use ApiPlatform\Core\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\Core\OpenApi\Model;
use ApiPlatform\Core\OpenApi\OpenApi;

/**
 * Class OpenApiFactory.
 */
class OpenApiFactory implements OpenApiFactoryInterface
{
    private OpenApiFactoryInterface $decorated;
    private int $maxIdentifierCount;

    /**
     * OpenApiFactory constructor.
     *
     * @param OpenApiFactoryInterface $decorated
     * @param int $bindApiMaxIdentifiers
     */
    public function __construct(OpenApiFactoryInterface $decorated, int $bindApiMaxIdentifiers)
    {
        $this->decorated = $decorated;
        $this->maxIdentifierCount = $bindApiMaxIdentifiers;
    }

    /**
     * {@inheritDoc}
     */
    public function __invoke(array $context = []): OpenApi
    {
        $openApi = $this->decorated->__invoke($context);
        $filteredPaths = new Model\Paths();

        /**
         * @var string $key
         * @var Model\PathItem $pathItem
         */
        foreach ($openApi->getPaths()->getPaths() as $key => $pathItem) {
            // We don't expose a item endpoint but api-platform requires one,
            // so we need to manually remove it from the docs.
            if ('/api/v2/covers/{id}' === $key) {
                // Do nothing
            }

            // Set max identifier count from .env in the parameter description for the collections endpoint
            if ('/api/v2/covers' === $key) {
                $parameters = [];
                $get = $pathItem->getGet();
                if ($get) {
                    foreach ($get->getParameters() as $parameter) {
                        /** @var Model\Parameter $parameter */
                        if ('identifiers' === $parameter->getName()) {
                            $description = sprintf($parameter->getDescription(), $this->maxIdentifierCount);
                            $parameter = $parameter->withDescription($description);

                            $schema = $parameter->getSchema();
                            $schema['maxLength'] = $this->maxIdentifierCount;
                            $parameter = $parameter->withSchema($schema);
                        }

                        $parameters[] = $parameter;
                    }

                    $get = $get->withParameters($parameters);
                    $filteredPaths->addPath($key, $pathItem->withGet($get));
                }
            }
        }

        return $openApi->withPaths($filteredPaths);
    }
}
