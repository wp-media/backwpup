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
 * Interface Queryable
 *
 * Ask information about the archive, such as status, content.
 *
 * @author Guido Scialfa <dev@guidoscialfa.com>
 */
interface ArchiveQueryable extends Archive
{
    /**
     * Check if an Archive is Valid or not
     *
     * @return bool
     */
    public function isValid();

    /**
     * Retrieve the List of Files Within the Archive
     *
     * @return string[]
     * @throws ArchiveException If the archive cannot be opened
     */
    public function content();

    /**
     * Count How Many Files And Directory the Archive Contains
     *
     * @return int
     * @throws ArchiveException If the archive cannot be read correctly
     */
    public function count();

    /**
     * Retrieve FileName by his Position Within the Archive
     *
     * @param int $index
     * @return string
     * @throws ArchiveException
     * @throws InvalidArgumentException If index it's not a valid index position
     */
    public function fileNameByIndex($index);
}
