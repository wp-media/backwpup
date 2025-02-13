<?php

declare(strict_types=1);

namespace Inpsyde\Restore\Api\Module\Restore;

use Inpsyde\Restore\Api\Module\Restore\Exception\RestorePathException;

/**
 * An interface for performing restores.
 *
 * @author Brandon Olivares <b.olivares@inpsyde.com>
 */
interface RestoreInterface
{
    /**
     * Perform a file restore.
     *
     * @throws RestorePathException
     * @throws \InvalidArgumentException
     */
    public function restore(): int;
}
