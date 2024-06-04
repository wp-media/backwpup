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
 * @author Guido Scialfa <dev@guidoscialfa.com>
 */
class LevelExtractor
{
    public const LEVEL_EMERGENCY = 'emergency';
    public const LEVEL_ALERT = 'alert';
    public const LEVEL_CRITICAL = 'critical';
    public const LEVEL_ERROR = 'error';
    public const LEVEL_WARNING = 'warning';
    public const LEVEL_NOTICE = 'notice';
    public const LEVEL_INFO = 'info';
    public const LEVEL_DEBUG = 'debug';

    /**
     * @var FileReader
     */
    private $fileReader;

    /**
     * @var LogLineParser
     */
    private $logLineParser;

    public function __construct(FileReader $fileReader, LogLineParser $logLineParser)
    {
        $this->fileReader = $fileReader;
        $this->logLineParser = $logLineParser;
    }

    /**
     * Extract Log Entries for Level Emergency.
     *
     * @throws InvalidArgumentException
     *
     * @return Log[]
     */
    public function extractEmergency(SplFileObject $file): array
    {
        return $this->extract($file, self::LEVEL_EMERGENCY);
    }

    /**
     * Extract Log Entries for Level Alert.
     *
     * @throws InvalidArgumentException
     *
     * @return Log[]
     */
    public function extractAlert(SplFileObject $file): array
    {
        return $this->extract($file, self::LEVEL_ALERT);
    }

    /**
     * Extract Log Entries for Level Critical.
     *
     * @throws InvalidArgumentException
     *
     * @return Log[]
     */
    public function extractCritical(SplFileObject $file): array
    {
        return $this->extract($file, self::LEVEL_CRITICAL);
    }

    /**
     * Extract Log Entries for Level Error.
     *
     * @throws InvalidArgumentException
     *
     * @return Log[]
     */
    public function extractError(SplFileObject $file): array
    {
        return $this->extract($file, self::LEVEL_ERROR);
    }

    /**
     * Extract Log Entries for Level Warning.
     *
     * @throws InvalidArgumentException
     *
     * @return Log[]
     */
    public function extractWarning(SplFileObject $file): array
    {
        return $this->extract($file, self::LEVEL_WARNING);
    }

    /**
     * Extract Log Entries for Level Notice.
     *
     * @throws InvalidArgumentException
     *
     * @return Log[]
     */
    public function extractNotice(SplFileObject $file): array
    {
        return $this->extract($file, self::LEVEL_NOTICE);
    }

    /**
     * Extract Log Entries for Level Info.
     *
     * @throws InvalidArgumentException
     *
     * @return Log[]
     */
    public function extractInfo(SplFileObject $file): array
    {
        return $this->extract($file, self::LEVEL_INFO);
    }

    /**
     * Extract Log Entries for Level Debug.
     *
     * @throws InvalidArgumentException
     *
     * @return Log[]
     */
    public function extractDebug(SplFileObject $file): array
    {
        return $this->extract($file, self::LEVEL_DEBUG);
    }

    /**
     * Extract the Log Entries equal to the Given Level.
     *
     * @param non-empty-string $level
     *
     * @throws InvalidArgumentException
     *
     * @return Log[]
     */
    private function extract(SplFileObject $file, string $level): array
    {
        Assert::readable($file->getPathname());

        $lines = $this->fileReader->lineByLine($file);

        if ($lines === []) {
            return [];
        }

        $logDataList = [];

        foreach ($lines as $line) {
            if (empty($line)) {
                continue;
            }

            $logData = $this->logLineParser->extractData($line);
            $logDataList[] = $logData;
        }

        return array_filter($logDataList, static function (Log $logData) use ($level): bool {
            return $level === $logData->level();
        });
    }
}
