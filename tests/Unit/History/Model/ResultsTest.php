<?php

/*
 * This file is part of the zenstruck/messenger-monitor-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Messenger\Monitor\Tests\Unit\History\Model;

use PHPUnit\Framework\TestCase;
use Zenstruck\Messenger\Monitor\History\Model\Results;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ResultsTest extends TestCase
{
    /**
     * @test
     */
    public function can_create_with_null(): void
    {
        $results = new Results(null);

        $this->assertCount(0, $results);
        $this->assertSame([], $results->all());
        $this->assertSame([], $results->successes());
        $this->assertSame([], $results->failures());
        $this->assertNull($results->jsonSerialize());
    }
}
