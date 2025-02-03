<?php

/*
 * This file is part of the zenstruck/messenger-monitor-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Messenger\Monitor\History\Storage;

use Elastica\Client as ElasticaClient;
use Elastica\Index;

final class ElasticaIndexManager
{
    private ElasticaClient $client;

    public function __construct(ElasticaClient $client)
    {
        $this->client = $client;
    }

    public static function createFromIndex(Index $index): self
    {
        $im = new self($index->getClient());
        $im->createIndex($index->getName());

        return $im;
    }

    /**
     * Creates the Elasticsearch index for ProcessedMessage objects.
     *
     * @param string $indexName the name of the index to create
     */
    public function createIndex(string $indexName): void
    {
        $index = $this->client->getIndex($indexName);

        if (!$index->exists()) {
            $index->create([
                'settings' => [
                    'number_of_shards' => 1,
                    'number_of_replicas' => 0,
                    'refresh_interval' => '1s',
                ],
                'mappings' => [
                    'properties' => [
                        'runId' => ['type' => 'integer'],
                        'attempt' => ['type' => 'integer'],
                        'type' => ['type' => 'keyword'],
                        'description' => ['type' => 'text'],
                        'dispatchedAt' => ['type' => 'date'],
                        'receivedAt' => ['type' => 'date'],
                        'finishedAt' => ['type' => 'date'],
                        'memoryUsage' => ['type' => 'integer'],
                        'transport' => ['type' => 'keyword'],
                        'tags' => ['type' => 'keyword'],
                        'waitTime' => ['type' => 'integer'],
                        'handleTime' => ['type' => 'integer'],
                        'failureType' => ['type' => 'keyword'],
                        'failureMessage' => ['type' => 'text'],
                        'results' => ['type' => 'text'],
                    ],
                ],
            ]);
        }
    }
}
