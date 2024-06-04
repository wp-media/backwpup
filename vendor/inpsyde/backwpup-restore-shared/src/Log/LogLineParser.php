<?php

declare(strict_types=1);

/*
 * This file is part of the BackWPup Restore Shared package.
 *
 * (c) Guido Scialfa <dev@guidoscialfa.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Restore\Log;

use InvalidArgumentException;

/**
 * @internal
 *
 * @author Guido Scialfa <dev@guidoscialfa.com>
 */
class LogLineParser
{
    private const REGEXP = '/^\[([0-9\-\s\:]+)\]\s+restore\.([a-zA-Z\.]+):\s+(.+?)\s+\[/';

    /**
     * Extract Data from Log Entry.
     *
     * @param non-empty-string $string
     *
     * @throws InvalidArgumentException
     */
    public function extractData(string $string): Log
    {
        $matched = preg_match(self::REGEXP, $string, $matches);

        if (!$matched) {
            return new NullLogData();
        }

        array_shift($matches);
        [$date, $level, $message] = $matches;

        $level = strtolower($level);

        if (empty($date) || empty($level) || empty($message)) {
            return new NullLogData();
        }

        return new LogData($date, $level, $message);
    }
}
