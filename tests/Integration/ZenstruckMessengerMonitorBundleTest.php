<?php

/*
 * This file is part of the zenstruck/messenger-monitor-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Messenger\Monitor\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;
use Symfony\Component\Serializer\Serializer;
use Zenstruck\Console\Test\InteractsWithConsole;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Messenger\Monitor\Stamp\MonitorStamp;
use Zenstruck\Messenger\Monitor\Tests\Fixture\TestService;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ZenstruckMessengerMonitorBundleTest extends KernelTestCase
{
    use InteractsWithConsole, ResetDatabase, ClockSensitiveTrait;

    /**
     * @test
     */
    public function autowires_services(): void
    {
        /** @var TestService $service */
        $service = self::getContainer()->get(TestService::class);

        $this->assertCount(1, $service->transports);
        $this->assertCount(0, $service->workers);
        $this->assertCount(0, $service->schedules);
    }

    /**
     * @test
     */
    public function run_messenger_monitor_command(): void
    {
        $this->executeConsoleCommand('messenger:monitor')
            ->assertSuccessful()
            ->assertOutputContains('[!] No workers running.')
            ->assertOutputContains('async   n/a')
        ;
    }

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
