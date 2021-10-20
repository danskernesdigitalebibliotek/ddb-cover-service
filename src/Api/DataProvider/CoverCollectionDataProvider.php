<?php
/**
 * @file
 * Cover collection data provider.
 *
 * @see https://api-platform.com/docs/core/data-providers/
 */

namespace App\Api\DataProvider;

use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Api\Dto\Cover;
use App\Api\Elastic\SearchServiceInterface;
use App\Api\Exception\IdentifierCountExceededException;
use App\Api\Exception\RequiredParameterMissingException;
use App\Api\Exception\UnknownIdentifierTypeException;
use App\Api\Exception\UnknownImageSizeException;
use App\Api\Factory\CoverFactory;
use App\Api\Statistics\CollectionStatsLogger;
use App\Service\NoHitService;
use App\Utils\Types\IdentifierType;
use ItkDev\MetricsBundle\Service\MetricsService;

/**
 * Class CoverCollectionDataProvider.
 */
final class CoverCollectionDataProvider implements ContextAwareCollectionDataProviderInterface, RestrictedDataProviderInterface
{
    private SearchServiceInterface $searchService;
    private CoverFactory $coverFactory;
    private NoHitService $noHitService;
    private CollectionStatsLogger $collectionStatsLogger;
    private MetricsService $metricsService;
    private int $maxIdentifierCount;

    /**
     * CoverCollectionDataProvider constructor.
     */
    public function __construct(SearchServiceInterface $searchService, CoverFactory $coverFactory, NoHitService $noHitService, CollectionStatsLogger $collectionStatsLogger, MetricsService $metricsService, int $bindApiMaxIdentifiers)
    {
        $this->searchService = $searchService;
        $this->coverFactory = $coverFactory;
        $this->noHitService = $noHitService;
        $this->collectionStatsLogger = $collectionStatsLogger;
        $this->metricsService = $metricsService;
        $this->maxIdentifierCount = $bindApiMaxIdentifiers;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return Cover::class === $resourceClass;
    }

    /**
     * {@inheritdoc}
     *
     * @throws RequiredParameterMissingException
     * @throws UnknownIdentifierTypeException
     * @throws IdentifierCountExceededException
     * @throws UnknownImageSizeException
     */
    public function getCollection(string $resourceClass, string $operationName = null, array $context = []): \Traversable
    {
        $identifierType = $this->getIdentifierType($context);
        $isIdentifiers = $this->getIdentifiers($context);
        $imageSizes = $this->getSizes($context);

        $results = $this->searchService->search($identifierType, $isIdentifiers);

        $foundIdentifiers = [];
        foreach ($results as $result) {
            $foundIdentifiers[] = $result['isIdentifier'];

            yield $this->coverFactory->createCoverDto($identifierType, $imageSizes, $result);
        }

        $this->collectionStatsLogger->logRequest($identifierType, $isIdentifiers, $results);
        $this->noHitService->handleSearchNoHits($identifierType, $isIdentifiers, $foundIdentifiers);
    }

    /**
     * Get the identifier type from the request context.
     *
     * @param array $context The Api-platform request context
     *
     * @return string The identifier type (e.g. 'pid', 'isbn', etc).
     *
     * @throws RequiredParameterMissingException
     * @throws UnknownIdentifierTypeException
     */
    private function getIdentifierType(array $context): string
    {
        if (!array_key_exists('filters', $context) && !array_key_exists('type', $context['filters'])) {
            throw new RequiredParameterMissingException('"type" parameter is required');
        }

        $type = $context['filters']['type'];

        if (in_array($context['filters']['type'], IdentifierType::getTypeList(), true)) {
            return $type;
        }

        $this->metricsService->counter('unknown_identifier_type', 'An unknown identifier type in call', 1, ['type' => 'rest']);
        throw new UnknownIdentifierTypeException($type.' is an unknown identifier type');
    }

    /**
     * Get IS identifiers from request context.
     *
     * @param array $context The Api-platform request context
     *
     * @return array Array of identifiers
     *
     * @throws RequiredParameterMissingException If the 'id' parameter is not found in the request
     * @throws IdentifierCountExceededException
     */
    private function getIdentifiers(array $context): array
    {
        if (!array_key_exists('filters', $context) || !array_key_exists('identifiers', $context['filters'])) {
            throw new RequiredParameterMissingException('"identifiers" parameter is required');
        }

        $identifiers = $context['filters']['identifiers'];

        if (!$identifiers) {
            throw new RequiredParameterMissingException('The "identifiers" parameter is required');
        }

        $isIdentifiers = explode(',', $identifiers);
        $isIdentifiers = \is_array($isIdentifiers) ? $isIdentifiers : [$isIdentifiers];

        $identifierCount = count($isIdentifiers);
        if ($identifierCount > $this->maxIdentifierCount) {
            $this->metricsService->counter('max_identifier_exceeded', 'Maximum identifiers per request exceeded', 1, ['type' => 'rest']);
            throw new IdentifierCountExceededException('Maximum identifiers per request exceeded. '.$this->maxIdentifierCount.' allowed. '.$identifierCount.' received.');
        }

        return $isIdentifiers;
    }

    /**
     * Get sizes from request context.
     *
     * @param array $context The Api-platform request context
     *
     * @return array Array with size names
     *
     * @throws UnknownImageSizeException
     */
    private function getSizes(array $context): array
    {
        if (array_key_exists('filters', $context) && array_key_exists('sizes', $context['filters'])) {
            $sizes = $context['filters']['sizes'];
            if (empty($sizes)) {
                throw new UnknownImageSizeException('The "sizes" parameter cannot be empty. Either omit the parameter or submit a list of valid image sizes.');
            }

            $sizes = explode(',', $sizes);
            $sizes = array_map('trim', $sizes);
            $sizes = array_map('strtolower', $sizes);

            $validSizes = $this->coverFactory->getValidImageSizes();

            $diff = array_diff($sizes, $validSizes);
            if (!empty($diff)) {
                $this->metricsService->counter('unknown_images_size', 'Unknown images size(s) in request', 1, ['type' => 'rest']);
                throw new UnknownImageSizeException('Unknown images size(s): '.implode(', ', $diff).' - Valid sizes are '.implode(', ', $validSizes));
            }
        } else {
            $sizes = ['default'];
        }

        return $sizes;
    }
}
