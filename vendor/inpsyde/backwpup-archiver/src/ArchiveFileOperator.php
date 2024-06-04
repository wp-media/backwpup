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

use SplFileInfo;

/**
 * Class Operator
 *
 * Operator is an interface that is capable of operations to an Archive instance.
 * Basically it's a CRUD interface.
 *
 * @author Guido Scialfa <dev@guidoscialfa.com>
 */
interface ArchiveFileOperator extends ArchiveQueryable
{
    /**
     * Add a File or Directory into the Archive
     *
     * @param SplFileInfo $file
     * @return void
     * @throws FileException In case the file cannot be added because it's unreadable
     * @throws ArchiveException In case the file cannot be added because of internal reason
     */
    public function add(SplFileInfo $file);

    /**
     * Fetch a File by the Given File
     *
     * @param SplFileInfo $file
     * @return SplFileInfo
     * @throws ArchiveException If the file cannot be extracted
     */
    public function fetch(SplFileInfo $file);

    /**
     * Check if the Given File Exists in the Archive
     *
     * @param SplFileInfo $file
     * @return bool
     * @throws ArchiveException If for some reason the archive content cannot be read
     */
    public function exists(SplFileInfo $file);

    /**
     * Replace an Existing File with the Given One
     *
     * @param SplFileInfo $file
     * @return void
     * @throws ArchiveException If the file cannot be replaced
     * @throws FileException If the file cannot be replaced
     */
    public function replace(SplFileInfo $file);

    /**
     * Remove a File from the Archive
     *
     * @param SplFileInfo $file
     * @return void
     * @throws ArchiveException If the file cannot be extracted
     */
    public function remove(SplFileInfo $file);
}
