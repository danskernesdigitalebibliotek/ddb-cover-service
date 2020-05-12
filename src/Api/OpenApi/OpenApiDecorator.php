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

    /**
     * OpenApiDecorator constructor.
     *
     * @param NormalizerInterface $decorated
     */
    public function __construct(NormalizerInterface $decorated)
    {
        $this->decorated = $decorated;
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
