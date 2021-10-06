<?php
/**
 * @file
 * Command to create an dynamic index template elasticsearch.
 */

namespace App\Command\Elastic;

use Elasticsearch\ClientBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class CreateDynamicStatsTemplateCommand.
 */
class CreateDynamicStatsTemplateCommand extends Command
{
    private string $elasticHost;
    private string $elasticStatsIndexPrefix;

    protected static $defaultName = 'app:elastic:create-stats-template';

    /**
     * CreateDynamicStatsTemplateCommand constructor.
     *
     * @param string $bindElasticSearchUrl
     *   The ElasticSearch endpoint url
     * @param string $bindElasticStatsIndexPrefix
     *   The prefix for statistics indices
     */
    public function __construct(string $bindElasticSearchUrl, string $bindElasticStatsIndexPrefix)
    {
        parent::__construct();

        $this->elasticHost = $bindElasticSearchUrl;
        $this->elasticStatsIndexPrefix = $bindElasticStatsIndexPrefix;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setDescription('Create dynamic stats index template. Command is idempotent and can safely be called multiple times.');
    }

    /** {@inheritdoc} */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $result = $this->createStatsTemplate();

            if ($result) {
                $io->success('Dynamic index template created.');

                return 0;
            }

            $io->error('Unknown error when creating template');

            return 1;
        } catch (\Exception $exception) {
            $io->error($exception->getMessage());

            return 1;
        }
    }

    /**
     * Create a dynamic index template to ensure proper indexing in ElasticSearch.
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/6.8/indices-templates.html
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/6.8/dynamic-templates.html
     *
     * @return bool
     *    Return true on success, false on failure
     */
    private function createStatsTemplate(): bool
    {
        $client = ClientBuilder::create()->setHosts([$this->elasticHost])->build();

        $params = [
            'name' => 'statistics_index_template',
            'body' => [
                'index_patterns' => [
                    $this->elasticStatsIndexPrefix.'*',
                ],
                'mappings' => [
                    'logs' => [
                        'dynamic_templates' => [
                            [
                                // Prevent ISBNs from being indexed as 'long'
                                'identifier_as_keyword' => [
                                    'match' => 'identifier',
                                    'mapping' => [
                                        'type' => 'keyword',
                                    ],
                                ],
                            ], [
                                // Prevent ISBNs from being indexed as 'long'
                                'isIdentifier_as_keyword' => [
                                    'match' => 'isIdentifier',
                                    'mapping' => [
                                        'type' => 'keyword',
                                    ],
                                ],
                            ], [
                                // Use proper ip-address type
                                'remoteIP_as_ip' => [
                                    'match' => 'remoteIP',
                                    'mapping' => [
                                        'type' => 'ip',
                                    ],
                                ],
                            ], [
                                // Always use 'keyword' for strings
                                'strings_as_keyword' => [
                                    'match_mapping_type' => 'string',
                                    'mapping' => [
                                        'type' => 'keyword',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            // Overwrite if template exists
            'create' => false,
        ];

        $result = $client->indices()->putTemplate($params);

        return array_key_exists('acknowledged', $result) && true === $result['acknowledged'];
    }
}
