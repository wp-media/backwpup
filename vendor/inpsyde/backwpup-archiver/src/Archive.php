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

use InvalidArgumentException;

/**
 * Interface Archive
 *
 * Archiver is an interface capable of basic operation with an archive.
 *
 * The interface define command methods only, in case of queries such as to know the content
 * of an archive or to know if it's a valid archive you have to implement ArchiveQueryable.
 *
 * If you need to operate with the content of the archive you should implement the Operator interface.
 *
 * @author Guido Scialfa <dev@guidoscialfa.com>
 */
interface Archive
{
    /**
     * Delete the Archive File
     *
     * @return void
     */
    public function delete();

    /**
     * Extract the Archive to the Given Path
     *
     * @param string $destination
     * @return void
     * @throws ArchiveException If the archive cannot be extracted
     * @throws InvalidArgumentException If destination doesn't exists
     */
    public function extractTo($destination);

    /**
     * Extract a File by It's Position Within the Archive
     *
     * @param int $index
     * @param string $destination
     * @return void
     * @throws ArchiveException If the file cannot be extracted
     * @throws InvalidArgumentException If index is not a valid position or destination doesn't exists
     */
    public function extractFileByIndex($index, $destination);
}
