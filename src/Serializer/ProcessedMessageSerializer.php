<?php

/*
 * This file is part of the zenstruck/messenger-monitor-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Messenger\Monitor\Serializer;

use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;
use Zenstruck\Messenger\Monitor\History\Model\ProcessedMessage;
use Zenstruck\Messenger\Monitor\Serializer\Normalizer\ProcessedMessageNormalizer;

final class ProcessedMessageSerializer
{
    private static ?Serializer $serializer = null;

    /**
     * Converts a ProcessedMessage into an array.
     *
     * @return array<string, mixed>
     *
     * @throws \UnexpectedValueException if normalization does not yield an array
     */
    public static function toArray(ProcessedMessage $message): array
    {
        $result = self::getSerializer()->normalize($message);
        if (!\is_array($result)) {
            throw new \UnexpectedValueException('Normalized value is not an array.');
        }

        return $result;
    }

    /**
     * Recreates a ProcessedMessage object from normalized data.
     *
     * @param array<string, mixed>           $data
     * @param string                         $id          the document ID from Elasticsearch
     * @param class-string<ProcessedMessage> $entityClass
     */
    public static function fromArray(array $data, string $id, string $entityClass): ProcessedMessage
    {
        /** @var ProcessedMessage $object */
        $object = self::getSerializer()->denormalize($data, $entityClass);
        if ($object instanceof $entityClass && \method_exists($object, 'setId')) {
            $object->setId($id);
        }

        return $object;
    }

    private static function getSerializer(): Serializer
    {
        if (null === self::$serializer) {
            self::$serializer = new Serializer(
                [new ProcessedMessageNormalizer()],
                [new JsonEncoder()]
            );
        }

        return self::$serializer;
    }
}
