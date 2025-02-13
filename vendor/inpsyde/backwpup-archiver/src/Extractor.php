<?php

/*
 * This file is part of the BackWPup Archiver package.
 *
 * (c) Inpsyde <hello@inpsyde.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\BackWPup\Archiver;

use Inpsyde\Assert\Assert;
use InvalidArgumentException;
use OutOfRangeException;
use Psr\Log\LoggerInterface;

/**
 * Class Extractor
 *
 * @author Guido Scialfa <dev@guidoscialfa.com>
 */
class Extractor
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Factory
     */
    private $operatorFactory;

    /**
     * Extractor constructor
     * @param LoggerInterface $logger
     * @param Factory $operatorFactory
     */
    public function __construct(LoggerInterface $logger, Factory $operatorFactory)
    {
        $this->logger = $logger;
        $this->operatorFactory = $operatorFactory;
    }

    /**
     * Extract all Files From the Archive Starting by the Given Offset
     *
     * TODO May be better to use a dispatcher than a callback?
     *
     * @param $archiveFile
     * @param string $destinationPath
     * @param int $offset
     * @param callable $afterExtractionCallback
     * @return void
     * @throws ArchiveException
     * @throws OutOfRangeException
     * @throws InvalidArgumentException
     */
    public function extractByOffset(
        $archiveFile,
        $destinationPath,
        $offset,
        $afterExtractionCallback = null
    ) {

        Assert::fileExists($archiveFile);
        Assert::fileExists($destinationPath);
        Assert::integer($offset);
        $afterExtractionCallback and Assert::isCallable($afterExtractionCallback);

        $operator = $this->operatorFactory->create($archiveFile);
        $filesNumber = $operator->count();

        if (!$filesNumber) {
            return;
        }

        if ($offset >= $filesNumber) {
            $allowed = $filesNumber - 1;
            throw new OutOfRangeException(
                "Cannot start to extract files at index {$offset}, max {$allowed} allowed."
            );
        }

        $this->maybeOpened($operator);
        for ($index = $offset; $index < $filesNumber; ++$index) {
            try {
                $operator->extractFileByIndex($index, $destinationPath);
            } catch (ArchiveException $exc) {
                $this->logger->error($exc->getMessage());
            }

            $afterExtractionCallback and $afterExtractionCallback(
                $this->currentExtractInfo($operator, $index, $destinationPath)
            );
        }
        $this->maybeClosed($operator);
    }

    /**
     * May be the Archive Need to be Opened
     *
     * @param ArchiveFileOperator $operator
     *
     * @return void
     * @throws ArchiveException
     */
    private function maybeOpened(ArchiveFileOperator $operator)
    {
        if ($operator instanceof Closable) {
            $operator->open();
        }
    }

    /**
     * May be the Archive was Opened and Need to be Closed
     *
     * @param ArchiveFileOperator $operator
     *
     * @return void
     */
    private function maybeClosed(ArchiveFileOperator $operator)
    {
        if ($operator instanceof Closable) {
            $operator->close();
        }
    }

    /**
     * Retrieve the Current Data Information for the Extracted File
     *
     * @param ArchiveFileOperator $operator
     * @param int $index
     * @param string $destinationPath
     * @return CurrentExtractInfo
     * @throws ArchiveException
     * @throws InvalidArgumentException
     */
    private function currentExtractInfo(ArchiveFileOperator $operator, $index, $destinationPath)
    {
        $count = $operator->count();
        $fileName = $operator->fileNameByIndex($index);

        return new CurrentExtractInfo($count, $index, $fileName, $destinationPath);
    }
}
