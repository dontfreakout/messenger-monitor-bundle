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

use Elastica\Aggregation\Avg;
use Elastica\Aggregation\Filter as AggFilter;
use Elastica\Aggregation\Terms;
use Elastica\Client as ElasticaClient;
use Elastica\Document;
use Elastica\Exception\NotFoundException as ElasticaNotFoundException;
use Elastica\Query;
use Elastica\Query\BoolQuery;
use Elastica\Query\Exists;
use Elastica\Query\Range;
use Elastica\Query\Term;
use Elastica\Query\Wildcard;
use Symfony\Component\Messenger\Envelope;
use Zenstruck\Collection;
use Zenstruck\Collection\ArrayCollection;
use Zenstruck\Messenger\Monitor\History\Model\MessageTypeMetric;
use Zenstruck\Messenger\Monitor\History\Model\ProcessedMessage;
use Zenstruck\Messenger\Monitor\History\Model\Results;
use Zenstruck\Messenger\Monitor\History\Serializer\ProcessedMessageSerializer;
use Zenstruck\Messenger\Monitor\History\Specification;
use Zenstruck\Messenger\Monitor\History\Storage;

/**
 * Storage adapter for Elasticsearch using ruflin/Elastica.
 *
 * This adapter implements the Storage interface by converting Specification
 * filters and aggregations into Elastica DSL queries.
 *
 * @internal
 *
 * @template T of ProcessedMessage
 */
final class ElasticaStorage implements Storage
{
    /** @var \Elastica\Index */
    private $index;

    /** @var class-string<T> */
    private string $entityClass;

    /**
     * @param ElasticaClient  $client      the Elastica client instance
     * @param string          $indexName   the Elasticsearch index name
     * @param class-string<T> $entityClass the entity class, must extend ProcessedMessage
     */
    public function __construct(ElasticaClient $client, string $indexName, string $entityClass)
    {
        $this->index = $client->getIndex($indexName);
        $this->index->exists() || $this->createIndex();
        $this->entityClass = $entityClass;
    }

    public function find(mixed $id): ?ProcessedMessage
    {
        try {
            $doc = $this->index->getDocument($id);

            return $this->hydrateMessage($doc->getData(), $doc->getId());
        } catch (ElasticaNotFoundException $e) {
            return null;
        }
    }

    /**
     * @return Collection<int,ProcessedMessage>
     */
    public function filter(Specification $specification): Collection
    {
        $boolQuery = $this->buildQuery($specification);
        $query = new Query();
        $query->setQuery($boolQuery);
        $query->setSort($this->buildSort($specification));

        $resultSet = $this->index->search($query);
        $messages = [];
        foreach ($resultSet->getDocuments() as $doc) {
            $messages[] = $this->hydrateMessage($doc->getData(), $doc->getId());
        }

        return new ArrayCollection($messages);
    }

    public function purge(Specification $specification): int
    {
        $boolQuery = $this->buildQuery($specification, false);
        $query = new Query();
        $query->setQuery($boolQuery);
        $responseData = $this->index->deleteByQuery($query)->getData();

        return (int) ($responseData['deleted'] ?? 0);
    }

    public function save(Envelope $envelope, Results $results, ?\Throwable $exception = null): void
    {
        /** @var T $object */
        $object = new $this->entityClass($envelope, $results, $exception);
        $data = ProcessedMessageSerializer::toArray($object);
        $id = $object->id();
        $id = null !== $id ? (string) $id : null;
        $doc = new Document($id, $data);
        $this->index->addDocument($doc);
        $this->index->refresh();
    }

    public function delete(mixed $id): void
    {
        try {
            $this->index->deleteById($id);
        } catch (\Exception $e) {
            // Ignore errors (e.g. document not found).
        }
    }

    /**
     * @return int|null milliseconds
     */
    public function averageWaitTime(Specification $specification): ?int
    {
        $boolQuery = $this->buildQuery($specification, false);
        $query = new Query();
        $query->setQuery($boolQuery);
        $query->setSize(0);

        $avgAgg = new Avg('avg_wait_time');
        $avgAgg->setField('waitTime');
        $query->addAggregation($avgAgg);

        $resultSet = $this->index->search($query);
        $data = $resultSet->getResponse()->getData();

        return isset($data['aggregations']['avg_wait_time']['value'])
            ? (int) $data['aggregations']['avg_wait_time']['value']
            : null;
    }

    /**
     * @return int|null milliseconds
     */
    public function averageHandlingTime(Specification $specification): ?int
    {
        $boolQuery = $this->buildQuery($specification, false);
        $query = new Query();
        $query->setQuery($boolQuery);
        $query->setSize(0);

        $avgAgg = new Avg('avg_handling_time');
        $avgAgg->setField('handleTime');
        $query->addAggregation($avgAgg);

        $resultSet = $this->index->search($query);
        $data = $resultSet->getResponse()->getData();

        return isset($data['aggregations']['avg_handling_time']['value'])
            ? (int) $data['aggregations']['avg_handling_time']['value']
            : null;
    }

    public function count(Specification $specification): int
    {
        $boolQuery = $this->buildQuery($specification, false);
        $query = new Query();
        $query->setQuery($boolQuery);
        $query->setSize(0);
        $resultSet = $this->index->search($query);

        return $resultSet->getTotalHits();
    }

    /**
     * @return Collection<int,MessageTypeMetric>
     */
    public function perMessageTypeMetrics(Specification $specification): Collection
    {
        $totalSeconds = $specification->snapshot($this)->totalSeconds();
        $boolQuery = $this->buildQuery($specification->ignoreMessageType(), false);
        $query = new Query();
        $query->setQuery($boolQuery);
        $query->setSize(0);

        $termsAgg = new Terms('by_type');
        $termsAgg->setField('type.keyword');
        $termsAgg->setSize(1000);

        // Create a filter aggregation for failure_count.
        $filterAgg = new AggFilter('failure_count');
        $existsQuery = new Exists('failureType');
        $filterAgg->setParam('query', $existsQuery->toArray());
        $termsAgg->addAggregation($filterAgg);

        // Sub-aggregation for average wait time.
        $avgWaitAgg = new Avg('avg_wait_time');
        $avgWaitAgg->setField('waitTime');
        $termsAgg->addAggregation($avgWaitAgg);

        // Sub-aggregation for average handling time.
        $avgHandlingAgg = new Avg('avg_handling_time');
        $avgHandlingAgg->setField('handleTime');
        $termsAgg->addAggregation($avgHandlingAgg);

        $query->addAggregation($termsAgg);

        $resultSet = $this->index->search($query);
        $data = $resultSet->getResponse()->getData();
        $buckets = $data['aggregations']['by_type']['buckets'] ?? [];
        $metrics = [];
        foreach ($buckets as $bucket) {
            $metrics[] = new MessageTypeMetric(
                $bucket['key'],
                $bucket['doc_count'],
                $bucket['failure_count']['doc_count'],
                isset($bucket['avg_wait_time']['value']) ? (int) $bucket['avg_wait_time']['value'] : 0,
                isset($bucket['avg_handling_time']['value']) ? (int) $bucket['avg_handling_time']['value'] : 0,
                $totalSeconds,
            );
        }

        return new ArrayCollection($metrics);
    }

    /**
     * @return Collection<int,class-string>
     */
    public function availableMessageTypes(Specification $specification): Collection
    {
        $boolQuery = $this->buildQuery($specification->ignoreMessageType(), false);
        $query = new Query();
        $query->setQuery($boolQuery);
        $query->setSize(0);

        $termsAgg = new Terms('types');
        $termsAgg->setField('type.keyword');
        $termsAgg->setSize(1000);
        $termsAgg->setOrder('_key', 'asc');
        $query->addAggregation($termsAgg);

        $resultSet = $this->index->search($query);
        $data = $resultSet->getResponse()->getData();
        $buckets = $data['aggregations']['types']['buckets'] ?? [];
        /** @var class-string[] $types */
        $types = \array_values(\array_map(static fn(array $bucket): string => $bucket['key'], $buckets));

        return new ArrayCollection($types);
    }

    /**
     * Builds an Elasticsearch DSL query based on the Specification.
     *
     * The Specification’s array is expected to contain:
     * - from: ?\DateTimeImmutable,
     * - to: ?\DateTimeImmutable,
     * - status: self::SUCCESS|self::FAILED|null,
     * - message_type: ?string,
     * - transport: ?string,
     * - tags: string[],
     * - not_tags: string[],
     * - sort: self::ASC|self::DESC,
     * - run_id: int|null,
     *
     * @param bool $order unused here but kept for parity
     */
    private function buildQuery(Specification $specification, bool $order = true): BoolQuery
    {
        $data = $specification->toArray();
        $from = $data['from'];
        $to = $data['to'];
        $status = $data['status'];
        $messageType = $data['message_type'];
        $transport = $data['transport'];
        $tags = $data['tags'];
        $notTags = $data['not_tags'];
        $runId = $data['run_id'];

        $boolQuery = new BoolQuery();

        if ($from) {
            $rangeQuery = new Range('finishedAt', ['gte' => $from->format('c')]);
            $boolQuery->addFilter($rangeQuery);
        }
        if ($to) {
            $rangeQuery = new Range('finishedAt', ['lte' => $to->format('c')]);
            $boolQuery->addFilter($rangeQuery);
        }
        if ($messageType) {
            $termQuery = new Term();
            $termQuery->setTerm('type.keyword', $messageType);
            $boolQuery->addFilter($termQuery);
        }
        if ($transport) {
            $termQuery = new Term();
            $termQuery->setTerm('transport.keyword', $transport);
            $boolQuery->addFilter($termQuery);
        }
        if ($runId) {
            $termQuery = new Term();
            $termQuery->setTerm('runId', $runId);
            $boolQuery->addFilter($termQuery);
        }
        if (Specification::SUCCESS === $status) {
            $existsQuery = new Exists('failureType');
            $boolQuery->addMustNot($existsQuery);
        } elseif (Specification::FAILED === $status) {
            $existsQuery = new Exists('failureType');
            $boolQuery->addFilter($existsQuery);
        }
        foreach ($tags as $tag) {
            $wildcardQuery = new Wildcard('tags', '*'.$tag.'*');
            $boolQuery->addMust($wildcardQuery);
        }
        foreach ($notTags as $notTag) {
            $wildcardQuery = new Wildcard('tags', '*'.$notTag.'*');
            $boolQuery->addMustNot($wildcardQuery);
        }

        return $boolQuery;
    }

    /**
     * Builds the sort portion of the query based on the Specification.
     *
     * @return array<int, array<string, array{order: string}>>
     */
    private function buildSort(Specification $specification): array
    {
        $data = $specification->toArray();
        $sort = $data['sort'];

        return [
            [
                'finishedAt' => [
                    'order' => 'desc' === \mb_strtolower($sort) ? 'desc' : 'asc',
                ],
            ],
        ];
    }

    /**
     * Converts an Elasticsearch document into a ProcessedMessage.
     *
     * @param mixed $data
     * @param mixed $id
     *
     * @throws \UnexpectedValueException if $data is not an array or $id is not a string
     */
    private function hydrateMessage($data, $id): ProcessedMessage
    {
        if (!\is_array($data)) {
            throw new \UnexpectedValueException('Document data must be an array.');
        }
        if (!\is_string($id)) {
            throw new \UnexpectedValueException('Document id must be a string.');
        }

        return ProcessedMessageSerializer::fromArray($data, $id, $this->entityClass);
    }

    private function createIndex(): void
    {
        ElasticaIndexManager::createFromIndex($this->index);
    }
}
