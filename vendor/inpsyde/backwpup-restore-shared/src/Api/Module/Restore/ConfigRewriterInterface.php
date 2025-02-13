<?php

declare(strict_types=1);

namespace Inpsyde\Restore\Api\Module\Restore;

use Inpsyde\Restore\Api\Exception\FileSystemException;
use Inpsyde\Restore\Api\Module\Restore\Exception\ConfigFileException;
use Inpsyde\Restore\Api\Module\Restore\Exception\ConfigFileNotFoundException;

/**
 * Interface for rewriting wp-config.php.
 *
 * This is currently part of {@link RestoreFiles} but should probably
 * be separated into its own class eventually.
 *
 * @author Brandon Olivares <b.olivares@inpsyde.com>
 */
interface ConfigRewriterInterface
{
    /**
     * Rewrite config file with new database credentials.
     *
     * @throws ConfigFileNotFoundException If the config file cannot be found
     * @throws FileSystemException         If the config file is not writable
     * @throws ConfigFileException         If the config file cannot be parsed
     */
    public function rewriteConfig(): void;
}
