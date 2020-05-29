<?php
/**
 * @file
 * OpenApi Service Decorator
 *
 * @see https://api-platform.com/docs/core/swagger/#overriding-the-openapi-specification
 */

namespace App\Api\OpenApi;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Class OpenApiDecorator.
 */
final class OpenApiDecorator implements NormalizerInterface
{
    private $decorated;
    private $maxIdentifierCount;

    /**
     * OpenApiDecorator constructor.
     *
     * @param NormalizerInterface $decorated
     * @param int $maxIdentifierCount
     */
    public function __construct(NormalizerInterface $decorated, int $maxIdentifierCount)
    {
        $this->decorated = $decorated;
        $this->maxIdentifierCount = $maxIdentifierCount;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $docs = $this->decorated->normalize($object, $format, $context);

        // We don't expose a item endpoint but api-platform requires one,
        // so we need to manually remove it from the docs.
        unset($docs['paths']['/api/v2/covers/{id}']);

        // Remove "authorizationUrl". Not allowed for "password grant"
        unset($docs['components']['securitySchemes']['oauth']['flows']['password']['authorizationUrl']);

        // "scopes" should be object, not array
        $docs['components']['securitySchemes']['oauth']['flows']['password']['scopes'] = new \stdClass();

        // Set max identifier count from .env
        $description = sprintf($docs['paths']['/api/v2/covers']['get']['parameters'][1]['description'], $this->maxIdentifierCount);
        $docs['paths']['/api/v2/covers']['get']['parameters'][1]['description'] = $description;
        $docs['paths']['/api/v2/covers']['get']['parameters'][1]['schema']['maxLength'] = $this->maxIdentifierCount;

        return $docs;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $this->decorated->supportsNormalization($data, $format);
    }
}
