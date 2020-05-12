<?php


namespace App\Api\OpenApi;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class OpenApiDecorator implements NormalizerInterface
{
    private $decorated;

    public function __construct(NormalizerInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    public function normalize($object, $format = null, array $context = [])
    {
        $docs = $this->decorated->normalize($object, $format, $context);

        // We don't expose a item endpoint but api-platform requires one,
        // so we need to manually remove it from the docs.
        unset($docs['paths']['/api/v2/covers/{id}']);

        return $docs;
    }

    public function supportsNormalization($data, $format = null)
    {
        return $this->decorated->supportsNormalization($data, $format);
    }
}