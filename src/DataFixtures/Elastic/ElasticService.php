<?php
/**
 * @file
 * Service to manage data in elasticsearch.
 */

namespace App\DataFixtures\Elastic;

use App\DataFixtures\Faker\Search;
use Elasticsearch\ClientBuilder;
use FOS\ElasticaBundle\Configuration\ConfigManager;
use FOS\ElasticaBundle\Elastica\Client;

/**
 * Class ElasticService.
 */
class ElasticService
{
    private string $elasticHost;
    private ConfigManager $fosElasticaConfigManager;

    /**
     * ElasticService constructor.
     *
     * We are injecting IndexManager and Client from Fos\Elastica to use
     * their configuration. The aim is to replace Fos\Elastica completely
     * but until this is done this is the 'master' configuration we use.
     *
     * @param ConfigManager $fosElasticaConfigManager
     *   Fos\Elastica indexManager
     * @param Client $fosElasticaClient
     *   Fos\Elastica client
     */
    public function __construct(ConfigManager $fosElasticaConfigManager, Client $fosElasticaClient)
    {
        $clientConfig = $fosElasticaClient->getConfig();
        // Fos\Elastica has a trailing slash for the elasticsearch url. Elastics own client does not accept a trailing slash.
        $this->elasticHost = rtrim($clientConfig['connections'][0]['url'], '/');

        $this->fosElasticaConfigManager = $fosElasticaConfigManager;
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

        $indexName = $this->getIndexName();
        $params = ['body' => []];

        foreach ($searches as $search) {
            $params['body'][] = [
                'index' => [
                    '_index' => $indexName,
                    '_id' => $search->getId(),
                    '_type' => 'search',
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

        $result = $client->bulk($params);
    }

    /**
     * Get the index name configured for Fos\Elastica.
     *
     * @return string
     *   The name of the index configured
     */
    private function getIndexName(): string
    {
        $indexes = $this->fosElasticaConfigManager->getIndexNames();

        // Exactly 1 index should be configured.
        // @TODO When Fos\Elastica is removed this needs to be adapted for new implementation
        if (1 !== count($indexes)) {
            throw new \RuntimeException('Found '.count($indexes).' elastic indexes. Exactly 1 index expected');
        }

        $indexName = array_pop($indexes);
        $indexConfig = $this->fosElasticaConfigManager->getIndexConfiguration($indexName);

        // @TODO When Fos\Elastica is removed this needs to be adapted for new implementation
        $client = ClientBuilder::create()->setHosts([$this->elasticHost])->build();

        if (!$client->indices()->exists(['index' => $indexConfig->getElasticSearchName()])) {
            throw new \RuntimeException('Index must be created before populating it. Please run \'fos:elastica:create\' for the relevant env to create it.');
        }

        return $indexConfig->getElasticSearchName();
    }
}
