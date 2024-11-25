<?php

/*
 * This file is part of the zenstruck/messenger-monitor-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Messenger\Monitor\Tests\Fixture\Message;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MessageC
{
    public function __construct(
        public readonly mixed $return1 = null,
        public readonly mixed $return2 = null,
        public readonly bool $throw = false,
    ) {
    }
}
