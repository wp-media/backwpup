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

use RuntimeException;

/**
 * Class ArchiverException
 *
 * @internal
 *
 * @author Guido Scialfa <dev@guidoscialfa.com>
 */
class ArchiveException extends RuntimeException
{
    /**
     * Create a new Exception for When is Not Possible to Open an Archive
     *
     * @param string $errorCode
     * @return ArchiveException
     */
    public static function becauseArchiveCannotBeOpened($errorCode = null)
    {
        $message = $errorCode
            ? "Archive cannot be opened because of error: {$errorCode}"
            : 'Archive cannot be opened because of an expected error.';

        return new self($message);
    }

    /**
     * Create a new Exception Because File Cannot be Extracted
     *
     * @param $fileName
     * @return ArchiveException
     */
    public static function becauseFileCannotBeExtracted($fileName)
    {
        return new self("Cannot extract file {$fileName}");
    }

    /**
     * Because Archive Cannot be Extracted
     *
     * @param $fileName
     * @return ArchiveException
     */
    public static function becauseArchiveCannotBeExtracted($fileName)
    {
        return new self("Cannot extract archive: {$fileName}.");
    }

    /**
     * Create a new Exception Because File Cannot be Deleted From an Archive
     *
     * @param $fileName
     * @return ArchiveException
     */
    public static function becauseFileCannotBeDeleted($fileName)
    {
        return new self("Cannot delete file {$fileName}");
    }

    /**
     * Create a new Exception when the file cannot be add into the archive because internal issues
     *
     * @param $fileName
     * @return ArchiveException
     */
    public static function forFileCannotBeAddBecauseInternalError($fileName)
    {
        return new static("File {$fileName} cannot be add to the archive because internal error.");
    }

    /**
     * Create a new Exception for When the Content List Cannot be Retrieved
     *
     * @return ArchiveException
     */
    public static function forContentUnretrievable()
    {
        return new static('Internal error while trying to retrieve content from the archive.');
    }

    /**
     * Create a new Exception for an Invalid Index When Try to Retrieve a File From the Archive
     *
     * @param $index
     * @return ArchiveException
     */
    public static function forInvalidFileIndex($index)
    {
        return new static("Invalid file index: {$index}");
    }
}
