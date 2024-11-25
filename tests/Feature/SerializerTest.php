<?php

/*
 * This file is part of the zenstruck/messenger-monitor-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Messenger\Monitor\Tests\Feature;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;
use Symfony\Component\Serializer\Serializer;
use Zenstruck\Messenger\Monitor\Stamp\MonitorStamp;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class SerializerTest extends KernelTestCase
{
    use ClockSensitiveTrait;

    /**
     * @test
     */
    public function serialize_and_unserialize_monitor_stamp(): void
    {
        $now = self::mockTime(new \DateTimeImmutable('2024-11-11'));

        /** @var Serializer $serializer */
        $serializer = self::getContainer()->get('serializer');
        $monitorStamp = new MonitorStamp($now->now());

        $this->assertEquals($monitorStamp, $serializer->deserialize($serializer->serialize($monitorStamp, 'json'), MonitorStamp::class, 'json'));

        $now->sleep(2);

        $monitorStamp = $monitorStamp->markReceived('async');

        $this->assertEquals($monitorStamp, $serializer->deserialize($serializer->serialize($monitorStamp, 'json'), MonitorStamp::class, 'json'));

        $now->sleep(3);

        $monitorStamp = $monitorStamp->markFinished();

        $this->assertEquals($monitorStamp, $serializer->deserialize($serializer->serialize($monitorStamp, 'json'), MonitorStamp::class, 'json'));
    }
}
