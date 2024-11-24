<?php

/*
 * This file is part of the zenstruck/messenger-monitor-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Messenger\Monitor\Tests\Unit\EventListener;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\SendMessageToTransportsEvent;
use Zenstruck\Messenger\Monitor\EventListener\AddMonitorStampListener;
use Zenstruck\Messenger\Monitor\Stamp\MonitorStamp;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class AddMonitorStampListenerTest extends TestCase
{
    /**
     * @test
     */
    public function adds_monitor_stamp(): void
    {
        $listener = new AddMonitorStampListener();
        $envelope = new Envelope(new \stdClass());
        $event = new SendMessageToTransportsEvent($envelope, []);

        $this->assertNull($event->getEnvelope()->last(MonitorStamp::class));

        $listener->__invoke($event);

        $this->assertInstanceOf(MonitorStamp::class, $event->getEnvelope()->last(MonitorStamp::class));
    }
}
