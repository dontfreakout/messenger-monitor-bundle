<?php

/*
 * This file is part of the zenstruck/messenger-monitor-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Messenger\Monitor\Twig;

use Knp\Bundle\TimeBundle\DateTimeFormatter;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Zenstruck\Messenger\Monitor\History\Storage;
use Zenstruck\Messenger\Monitor\Schedules;
use Zenstruck\Messenger\Monitor\Transports;
use Zenstruck\Messenger\Monitor\Workers;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ViewHelper
{
    /**
     * @internal
     */
    public function __construct(
        public readonly Transports $transports,
        public readonly Workers $workers,
        public readonly ?Storage $storage,
        public readonly ?Schedules $schedules,
        public readonly ?DateTimeFormatter $timeFormatter,
        public readonly ?CsrfTokenManagerInterface $csrfTokenManager,
    ) {
    }

    public function canFormatDuration(): bool
    {
        return $this->timeFormatter && \method_exists($this->timeFormatter, 'formatDuration');
    }

    public function generateCsrfToken(string ...$parts): string
    {
        if (!$this->csrfTokenManager) {
            return '';
        }

        return $this->csrfTokenManager->getToken(self::csrfTokenId(...$parts));
    }

    public function validateCsrfToken(string $token, string ...$parts): void
    {
        if (!$this->csrfTokenManager) {
            return;
        }

        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(self::csrfTokenId(...$parts), $token))) {
            throw new HttpException(419, 'Invalid CSRF token.');
        }
    }

    private static function csrfTokenId(string ...$parts): string
    {
        return \implode('-', $parts);
    }
}
