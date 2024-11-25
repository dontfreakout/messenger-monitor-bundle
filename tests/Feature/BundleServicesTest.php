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
use Zenstruck\Messenger\Monitor\Tests\Fixture\TestService;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class BundleServicesTest extends KernelTestCase
{
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
}
