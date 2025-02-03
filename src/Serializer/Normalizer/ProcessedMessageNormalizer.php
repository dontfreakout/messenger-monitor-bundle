<?php

/*
 * This file is part of the zenstruck/messenger-monitor-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Messenger\Monitor\Serializer\Normalizer;

use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Zenstruck\Messenger\Monitor\History\Model\ProcessedMessage;

final class ProcessedMessageNormalizer implements NormalizerInterface, DenormalizerInterface, NormalizerAwareInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait, NormalizerAwareTrait;

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool // @phpstan-ignore-line
    {
        return $data instanceof ProcessedMessage;
    }

    public function normalize($data, ?string $format = null, array $context = []): array // @phpstan-ignore-line
    {
        /** @var ProcessedMessage $data */
        return [
            'id' => (string) $data->id(),
            'runId' => $data->runId(),
            'attempt' => $data->attempt(),
            'type' => (string) $data->type(),
            'description' => $data->description(),
            'dispatchedAt' => $data->dispatchedAt()->format('c'),
            'receivedAt' => $data->receivedAt()->format('c'),
            'finishedAt' => $data->finishedAt()->format('c'),
            'transport' => $data->transport(),
            'tags' => (string) $data->tags(),
            'results' => \json_encode($data->results(), \JSON_THROW_ON_ERROR),
            'failure' => $data->failure() ? (string) $data->failure() : null,
            'isFailure' => $data->isFailure(),
            'timeInQueue' => $data->timeInQueue(),
            'timeToHandle' => $data->timeToHandle(),
            'timeToProcess' => $data->timeToProcess(),
            'memoryUsage' => (string) $data->memoryUsage()->value(),
        ];
    }

    /**
     * @param array<string, mixed>           $data
     * @param class-string<ProcessedMessage> $type
     * @param array<string, mixed>           $context
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        $reflection = new \ReflectionClass($type);
        $object = $reflection->newInstanceWithoutConstructor();

        $propertyMap = [
            // 'id' => 'id',
            'runId' => 'runId',
            'attempt' => 'attempt',
            'type' => 'type',
            'description' => 'description',
            'dispatchedAt' => 'dispatchedAt',
            'receivedAt' => 'receivedAt',
            'finishedAt' => 'finishedAt',
            'memoryUsage' => 'memoryUsage',
            'transport' => 'transport',
            'tags' => 'tags',
            'results' => 'results',
            'failure' => 'failureType',
            'timeInQueue' => 'waitTime',
            'timeToHandle' => 'handleTime',
        ];

        foreach ($propertyMap as $dataKey => $propertyName) {
            if (\array_key_exists($dataKey, $data)) {
                $value = $data[$dataKey];

                if (\in_array($propertyName, ['dispatchedAt', 'receivedAt', 'finishedAt'], true)) {
                    $value = new \DateTimeImmutable($value);
                }

                if ('memoryUsage' === $propertyName || 'attempt' === $propertyName) {
                    $value = (int) $value;
                }

                if ('results' === $propertyName) {
                    $value = \json_decode($value, true, 512, \JSON_THROW_ON_ERROR);
                }

                $this->setPropertyValue($object, $propertyName, $value);
            }
        }

        return $object;
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool // @phpstan-ignore-line
    {
        return \is_subclass_of($type, ProcessedMessage::class) && \is_array($data);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [ProcessedMessage::class => true];
    }

    /**
     * Walks up the inheritance chain to set the value of a property, even if it's declared in a parent class.
     *
     * @param mixed $value
     */
    private function setPropertyValue(object $object, string $propertyName, $value): void
    {
        $reflection = new \ReflectionClass($object);
        while ($reflection) {
            if ($reflection->hasProperty($propertyName)) {
                /** @var \ReflectionProperty $property */
                $property = $reflection->getProperty($propertyName);
                $property->setAccessible(true);
                $property->setValue($object, $value);

                return;
            }
            $reflection = $reflection->getParentClass();
        }
    }
}
