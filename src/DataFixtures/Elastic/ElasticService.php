<?php
/**
 * @file
 * Service to manage data in elasticsearch.
 */

namespace App\DataFixtures\Elastic;

use App\DataFixtures\Faker\Search;
use Elasticsearch\ClientBuilder;

/**
 * Class ElasticService.
 */
class ElasticService
{
    /**
     * ElasticService constructor.
     */
    public function __construct(
        private readonly string $elasticHost,
        private readonly string $indexName,
    ) {
    }

    /**
     * Index the Search objects in elasticsearch.
     *
     * @param Search ...$searches
     *   Iterable of Search objects to index
     */
    public function index(Search ...$searches): void
    {
        if (empty($searches)) {
            return;
        }

        $client = ClientBuilder::create()->setHosts([$this->elasticHost])->build();
        if (!$client->indices()->exists(['index' => $this->indexName])) {
            $this->createIndex();
        }

        $params = ['body' => []];

        foreach ($searches as $search) {
            $params['body'][] = [
                'index' => [
                    '_index' => $this->indexName,
                    '_id' => $search->getId(),
                ],
            ];

            $params['body'][] = [
                'isIdentifier' => $search->getIsIdentifier(),
                'isType' => $search->getIsType(),
                'imageUrl' => $search->getImageUrl(),
                'imageFormat' => $search->getImageFormat(),
                'width' => $search->getWidth(),
                'height' => $search->getHeight(),
            ];
        }

        $client->bulk($params);
        $client->indices()->refresh(['index' => $this->indexName]);
    }

    /**
     * Create new index.
     */
    public function createIndex(): void
    {
        $client = ClientBuilder::create()->setHosts([$this->elasticHost])->build();
        $client->indices()->create([
            'index' => $this->indexName,
            'body' => [
                'settings' => [
                    'number_of_shards' => 5,
                    'number_of_replicas' => 0,
                ],
                'mappings' => [
                    'properties' => [
                        'isIdentifier' => [
                            'type' => 'keyword',
                        ],
                        'imageFormat' => [
                            'type' => 'keyword',
                        ],
                        'imageUrl' => [
                            'type' => 'text',
                        ],
                        'width' => [
                            'type' => 'integer',
                        ],
                        'isType' => [
                            'type' => 'keyword',
                        ],
                        'height' => [
                            'type' => 'integer',
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Delete current index.
     */
    public function deleteIndex(): void
    {
        $client = ClientBuilder::create()->setHosts([$this->elasticHost])->build();
        if ($client->indices()->exists(['index' => $this->indexName])) {
            $client->indices()->delete(['index' => $this->indexName]);
        }
    }
}
