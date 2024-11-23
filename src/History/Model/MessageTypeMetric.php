<?php

/*
 * This file is part of the zenstruck/messenger-monitor-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Messenger\Monitor\History\Model;

use Zenstruck\Messenger\Monitor\History\Metric;
use Zenstruck\Messenger\Monitor\Type;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MessageTypeMetric extends Metric
{
    private readonly Type $type;

    /**
     * @param class-string $class
     * @param float        $averageWaitTime     In seconds
     * @param float        $averageHandlingTime In seconds
     */
    public function __construct(
        string $class,
        private readonly int $totalCount,
        private readonly int $failureCount,
        private readonly float $averageWaitTime,
        private readonly float $averageHandlingTime,
        private readonly int $totalSeconds,
    ) {
        $this->type = new Type($class);
    }

    public function type(): Type
    {
        return $this->type;
    }

    public function averageWaitTime(): float
    {
        return $this->averageWaitTime;
    }

    public function averageHandlingTime(): float
    {
        return $this->averageHandlingTime;
    }

    public function failureCount(): int
    {
        return $this->failureCount;
    }

    public function totalCount(): int
    {
        return $this->totalCount;
    }

    protected function totalSeconds(): int
    {
        return $this->totalSeconds;
    }
}
