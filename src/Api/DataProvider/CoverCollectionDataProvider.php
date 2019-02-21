<?php
/**
 * @file
 * Cover collection data provider.
 *
 * @see https://api-platform.com/docs/core/data-providers/
 */

namespace App\Api\DataProvider;

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Api\Dto\Cover;
use App\Api\Exception\RequiredParameterMissingException;
use Symfony\Component\HttpFoundation\Request;

final class CoverCollectionDataProvider extends AbstractElasticSearchDataProvider implements CollectionDataProviderInterface, RestrictedDataProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return Cover::class === $resourceClass;
    }

    /**
     * {@inheritdoc}
     */
    public function getCollection(string $resourceClass, string $operationName = null): \Traversable
    {
        $request = $this->requestStack->getCurrentRequest();

        $identifierType = $this->getIdentifierType($request->getPathInfo());

        $isIdentifiers = $this->getIdentifiers($request);

        $elasticQuery = $this->buildElasticQuery($identifierType, $isIdentifiers);
        $search = $this->index->search($elasticQuery);
        $results = $search->getResults();

        $foundIdentifiers = [];

        foreach ($results as $result) {
            $data = $result->getData();
            $foundIdentifiers[] = $data['isIdentifier'];

            yield $this->factory->createIdentifierDto($identifierType, $data);
        }

        // @TODO Move logging logic to new EventListener an trigger on POST_READ, https://api-platform.com/docs/core/events/
        $this->logStatistics($identifierType, $isIdentifiers, $results, $request);

        // @TODO Move no hits logic to new EventListener an trigger on POST_READ, https://api-platform.com/docs/core/events/
        $notFoundIdentifiers = array_diff($isIdentifiers, $foundIdentifiers);
        if ($notFoundIdentifiers) {
            $this->registerSearchNoHits($identifierType, $notFoundIdentifiers);
        }
    }

    /**
     * Get IS identifiers from request parameters.
     *
     * @param Request $request
     *   The Symfony request
     *
     * @return array
     *   Array of identifiers given in the request parameters
     *
     * @throws RequiredParameterMissingException
     *   If the 'id' parameter is not found in the request
     */
    private function getIdentifiers(Request $request): array
    {
        $identifiers = $request->query->get('id');

        if (!$identifiers) {
            throw new RequiredParameterMissingException('The "id" parameter is required');
        }

        $isIdentifiers = explode(',', $identifiers);
        $isIdentifiers = \is_array($isIdentifiers) ? $isIdentifiers : [$isIdentifiers];

        return $isIdentifiers;
    }
}
