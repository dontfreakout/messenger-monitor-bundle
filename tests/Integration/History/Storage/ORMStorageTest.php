<?php

/*
 * This file is part of the zenstruck/messenger-monitor-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Messenger\Monitor\Tests\Integration\History\Storage;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Messenger\Monitor\History\Specification;
use Zenstruck\Messenger\Monitor\History\Storage;
use Zenstruck\Messenger\Monitor\Tests\Fixture\Factory\ProcessedMessageFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ORMStorageTest extends KernelTestCase
{
    use Factories, ResetDatabase;

    /**
     * @test
     */
    public function average_wait_time(): void
    {
        $start = new \DateTimeImmutable('2024-11-11');

        ProcessedMessageFactory::createOne([
            'dispatchedAt' => $start,
            'receivedAt' => $start->modify('+20 seconds'),
        ]);
        ProcessedMessageFactory::createOne([
            'dispatchedAt' => $start,
            'receivedAt' => $start->modify('+10 seconds'),
        ]);

        $this->assertSame(15, (int) $this->storage()->averageWaitTime(Specification::new()));
    }

    /**
     * @test
     */
    public function average_handling_time(): void
    {
        $start = new \DateTimeImmutable('2024-11-11');

        ProcessedMessageFactory::createOne([
            'receivedAt' => $start,
            'finishedAt' => $start->modify('+40 seconds'),
        ]);
        ProcessedMessageFactory::createOne([
            'receivedAt' => $start,
            'finishedAt' => $start->modify('+20 seconds'),
        ]);

        $this->assertSame(30, (int) $this->storage()->averageHandlingTime(Specification::new()));
    }

    private function storage(): Storage
    {
        return self::getContainer()->get('zenstruck_messenger_monitor.history.storage');
    }
}
