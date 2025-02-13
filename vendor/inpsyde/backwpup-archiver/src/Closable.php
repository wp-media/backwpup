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

/**
 * Interface Closable
 *
 * This interface identify archives that need to be opened and closed to perform operation on them.
 *
 * @author Guido Scialfa <dev@guidoscialfa.com>
 */
interface Closable
{
    /**
     * Create the Archive file
     *
     * @return void
     * @throws ArchiveException If the archive cannot be opened
     */
    public function create();

    /**
     * Open the Archive for Read
     *
     * @return void
     * @throws ArchiveException If the archive cannot be opened
     */
    public function open();

    /**
     * Close the Archive File
     *
     * @return void
     */
    public function close();
}
