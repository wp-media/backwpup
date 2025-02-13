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

use Exception;

/**
 * Class FileException
 *
 * @internal
 *
 * @author Guido Scialfa <dev@guidoscialfa.com>
 */
class FileException extends Exception
{
    /**
     * Create new Exception for File Not Readable
     *
     * @param $fileName
     * @return FileException
     */
    public static function becauseFileIsNotReadable($fileName)
    {
        return new self("File {$fileName} is not readable.");
    }
}
