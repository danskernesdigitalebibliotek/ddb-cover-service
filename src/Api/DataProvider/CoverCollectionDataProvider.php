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
use App\Api\Exception\RequiredParameterMissingException;
use App\Api\Exception\UnknownIdentifierTypeException;
use App\Api\Factory\CoverFactory;
use App\Api\Statistics\CollectionStatsLogger;
use App\Service\NoHitService;
use App\Utils\Types\IdentifierType;

final class CoverCollectionDataProvider implements ContextAwareCollectionDataProviderInterface, RestrictedDataProviderInterface
{
    private $searchService;
    private $coverFactory;
    private $noHitService;
    private $collectionStatsLogger;

    /**
     * CoverCollectionDataProvider constructor.
     *
     * @param SearchServiceInterface $searchService
     * @param CoverFactory $coverFactory
     * @param NoHitService $noHitService
     */
    public function __construct(SearchServiceInterface $searchService, CoverFactory $coverFactory, NoHitService $noHitService, CollectionStatsLogger $collectionStatsLogger)
    {
        $this->searchService = $searchService;
        $this->coverFactory = $coverFactory;
        $this->noHitService = $noHitService;
        $this->collectionStatsLogger = $collectionStatsLogger;
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
     * @param array $context
     *   The Api-platform request context
     *
     * @return string
     *   The identifier type (e.g. 'pid', 'isbn', etc).
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

        throw new UnknownIdentifierTypeException($type.' is an unknown identifier type');
    }

    /**
     * Get IS identifiers from request context.
     *
     * @param array $context
     *   The Api-platform request context
     *
     * @return array
     *   Array of identifiers
     *
     * @throws RequiredParameterMissingException If the 'id' parameter is not found in the request
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

        return $isIdentifiers;
    }

    /**
     * Get sizes from request context.
     *
     * @param array context
     *   The Api-platform request context
     *
     * @return array
     *   Array with size names
     */
    private function getSizes(array $context): array
    {
        if (array_key_exists('filters', $context) && array_key_exists('sizes', $context['filters'])) {
            $sizes = $context['filters']['sizes'];
        } else {
            $sizes = 'default';
        }

        $sizes = explode(',', $sizes);
        $sizes = \is_array($sizes) ? $sizes : [$sizes];

        return $sizes;
    }
}
