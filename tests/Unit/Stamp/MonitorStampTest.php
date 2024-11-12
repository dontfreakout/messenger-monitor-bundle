<?php

/*
 * This file is part of the zenstruck/messenger-monitor-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Messenger\Monitor\Tests\Unit\Stamp;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;
use Zenstruck\Messenger\Monitor\Stamp\MonitorStamp;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MonitorStampTest extends TestCase
{
    use ClockSensitiveTrait;

    /**
     * @test
     */
    public function to_array(): void
    {
        $time = self::mockTime();

        $stamp = new MonitorStamp($time->now());
        $array = $stamp->toArray();

        $this->assertSame(['runId', 'dispatchedAt'], \array_keys($array));
        $this->assertIsInt($runId = $array['runId']);
        $this->assertEquals($dispatchedAt = $time->now(), $array['dispatchedAt']);
        $this->assertEquals($array, MonitorStamp::from($array)->toArray());

        $time->sleep(2);

        $stamp = $stamp->markReceived('async');
        $array = $stamp->toArray();

        $this->assertSame(['runId', 'dispatchedAt', 'transport', 'receivedAt'], \array_keys($array));
        $this->assertSame($runId, $array['runId']);
        $this->assertSame('async', $array['transport']);
        $this->assertEquals($dispatchedAt, $array['dispatchedAt']);
        $this->assertEquals($receivedAt = $time->now(), $array['receivedAt']);

        $time->sleep(3);

        $stamp = $stamp->markFinished();
        $array = $stamp->toArray();

        $this->assertSame(['runId', 'dispatchedAt', 'transport', 'receivedAt', 'finishedAt', 'memoryUsage'], \array_keys($array));
        $this->assertSame($runId, $array['runId']);
        $this->assertSame('async', $array['transport']);
        $this->assertEquals($receivedAt, $array['receivedAt']);
        $this->assertEquals($dispatchedAt, $array['dispatchedAt']);
        $this->assertEquals($time->now(), $array['finishedAt']);
    }

    /**
     * @test
     */
    public function from_array_initial(): void
    {
        $stamp = MonitorStamp::from([
            'runId' => 123,
            'dispatchedAt' => $dispatchedAt = new \DateTimeImmutable(),
        ]);

        $this->assertSame(123, $stamp->runId());
        $this->assertSame($dispatchedAt, $stamp->dispatchedAt());
        $this->assertFalse($stamp->isReceived());
        $this->assertFalse($stamp->isFinished());
    }

    /**
     * @test
     */
    public function from_array_received(): void
    {
        $stamp = MonitorStamp::from([
            'runId' => 123,
            'dispatchedAt' => $dispatched = new \DateTimeImmutable(),
            'transport' => 'async',
            'receivedAt' => $receivedAt = new \DateTimeImmutable(),
        ]);

        $this->assertSame(123, $stamp->runId());
        $this->assertSame($dispatched, $stamp->dispatchedAt());
        $this->assertSame('async', $stamp->transport());
        $this->assertSame($receivedAt, $stamp->receivedAt());
        $this->assertFalse($stamp->isFinished());
    }

    /**
     * @test
     */
    public function from_array_finished(): void
    {
        $stamp = MonitorStamp::from([
            'runId' => 123,
            'dispatchedAt' => $dispatched = new \DateTimeImmutable(),
            'transport' => 'async',
            'receivedAt' => $receivedAt = new \DateTimeImmutable(),
            'finishedAt' => $finishedAt = new \DateTimeImmutable(),
            'memoryUsage' => 123456,
        ]);

        $this->assertSame(123, $stamp->runId());
        $this->assertSame($dispatched, $stamp->dispatchedAt());
        $this->assertSame('async', $stamp->transport());
        $this->assertSame($receivedAt, $stamp->receivedAt());
        $this->assertSame($finishedAt, $stamp->finishedAt());
        $this->assertSame(123456, $stamp->memoryUsage());
    }
}
