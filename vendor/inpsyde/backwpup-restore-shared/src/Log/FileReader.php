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

use Inpsyde\Assert\Assert;
use InvalidArgumentException;
use SplFileObject;

/**
 * @internal
 *
 * @author Guido Scialfa <dev@guidoscialfa.com>
 */
class FileReader
{
    /**
     * Read the Given File Line By Line.
     *
     * @throws InvalidArgumentException
     *
     * @return string[]
     */
    public function lineByLine(SplFileObject $file): array
    {
        Assert::readable($file->getPathname());

        $lines = [];

        $file->rewind();

        while (!$file->eof()) {
            $logData = $file->fgets();
            if ($logData !== false) {
                $lines[] = $logData;
            }
        }

        // Clean Lines
        $lines = array_map(static function (string $line): string {
            return preg_replace('~[\r\n]+~', '', $line) ?? '';
        }, $lines);

        return array_filter($lines);
    }
}
