<?php
/**
 * @file
 * Service to manage data in elasticsearch.
 */

namespace App\DataFixtures\Elastic;

use App\DataFixtures\Faker\Search;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;

/**
 * Class ElasticService.
 */
class ElasticService
{
    private string $elasticHost;
    private string $indexName;

    /**
     * ElasticService constructor.
     */
    public function __construct(string $bindElasticSearchUrl, string $bindElasticIndex)
    {
        $this->elasticHost = $bindElasticSearchUrl;
        $this->indexName = $bindElasticIndex;
    }

    /**
     * Index the Search objects in elasticsearch.
     *
     * @param Search ...$searches
     *   Iterable of Search objects to index
     */
    public function index(Search ...$searches): void
    {
        $client = ClientBuilder::create()->setHosts([$this->elasticHost])->build();
        $this->createIndex($client);

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
    }

    /**
     * Create new index.
     *
     * @param Client $client
     *
     * @return void
     */
    private function createIndex(Client $client): void
    {
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
}
