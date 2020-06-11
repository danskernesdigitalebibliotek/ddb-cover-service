<?php
/**
 * @file
 * Command to create an dynamic index template elasticsearch.
 */

namespace App\Command;

use Elasticsearch\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class ElasticStatsTemplateCommand.
 */
class ElasticStatsTemplateCommand extends Command
{
    private $client;

    protected static $defaultName = 'app:elastic:create-stats-template';

    /**
     * ElasticStatsTemplateCommand constructor.
     *
     * @param Client $client
     *   ElasticSearch Client
     */
    public function __construct(Client $client)
    {
        parent::__construct();

        $this->client = $client;
    }

    /** {@inheritdoc} */
    protected function configure()
    {
        $this->setDescription('Verify the stats index template');
    }

    /** {@inheritdoc} */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $result = $this->createStatsTemplate();

            if ($result['acknowledged']) {
                $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

                return 0;
            } else {
                $io->error('Unknown error when creating template');

                return 1;
            }
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
     * @return array
     */
    private function createStatsTemplate(): array
    {
        $params = [
            'name' => 'statistics_index_template',
            'body' => [
                'index_patterns' => [
                    'stats_*',
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

        return $this->client->indices()->putTemplate($params);
    }
}
