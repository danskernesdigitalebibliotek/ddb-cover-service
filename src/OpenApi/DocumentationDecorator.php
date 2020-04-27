<?php

/**
 * @file
 * Documentation decorator to override parts of the generated documentation.
 *
 * @see https://api-platform.com/docs/core/swagger/
 */

namespace App\OpenApi;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Class DocumentationDecorator.
 */
class DocumentationDecorator implements NormalizerInterface
{
    private $decorated;
    private $pathPrefix;

    /**
     * DocumentationDecorator constructor.
     *
     * @param NormalizerInterface $decorated
     *   Service to normalize an object into a set of arrays/scalars
     */
    public function __construct(NormalizerInterface $decorated, String $pathPrefix)
    {
        $this->decorated = $decorated;
        $this->pathPrefix = $pathPrefix;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        // Exclude all paths and definitions NOT related to 'Cover'. Api-platform
        // does not play nice with multiple {parameters} in path. As a workaround
        // Dto's are defined for each cover/{type}, aka. 'Isbn', 'Pid', etc.
        // Because these are annotated as @ApiResource we need to exclude them from
        // the docs to avoid duplicate definitions in the generated documentation.
        $docs = $this->decorated->normalize($object, $format, $context);

        $paths[$this->pathPrefix.'/cover/{type}'] = $docs['paths'][$this->pathPrefix.'/cover/{type}'];
        $paths[$this->pathPrefix.'/cover/{type}/{id}'] = $docs['paths'][$this->pathPrefix.'/cover/{type}/{id}'];
        $docs['paths'] = $paths;

        $definitions['Cover-read'] = $docs['definitions']['Cover-read'];
        $docs['definitions'] = $definitions;

        return $docs;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null): bool
    {
        return $this->decorated->supportsNormalization($data, $format);
    }
}
