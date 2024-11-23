<?php

/*
 * This file is part of the zenstruck/messenger-monitor-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Messenger\Monitor\History;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class Metric
{
    final public function failRate(): float
    {
        try {
            return $this->failureCount() / $this->totalCount();
        } catch (\DivisionByZeroError) {
            return 0;
        }
    }

    /**
     * @param positive-int $divisor Seconds
     */
    final public function handledPer(int $divisor): float
    {
        $interval = $this->totalSeconds() / $divisor;

        return $this->totalCount() / $interval;
    }

    final public function handledPerMinute(): float
    {
        return $this->handledPer(60);
    }

    final public function handledPerHour(): float
    {
        return $this->handledPer(60 * 60);
    }

    final public function handledPerDay(): float
    {
        return $this->handledPer(60 * 60 * 24);
    }

    /**
     * @return float In seconds
     */
    final public function averageProcessingTime(): float
    {
        return $this->averageWaitTime() + $this->averageHandlingTime();
    }

    /**
     * @return float In seconds
     */
    abstract public function averageWaitTime(): float;

    /**
     * @return float In seconds
     */
    abstract public function averageHandlingTime(): float;

    abstract public function failureCount(): int;

    abstract public function totalCount(): int;

    abstract protected function totalSeconds(): int;
}
