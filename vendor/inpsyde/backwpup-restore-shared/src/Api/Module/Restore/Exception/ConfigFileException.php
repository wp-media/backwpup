<?php

declare(strict_types=1);

/*
 * This file is part of the BackWPup Restore Shared package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Restore\Api\Module\Restore\Exception;

use Inpsyde\Restore\Api\Exception\RestoreExceptionInterface;
use RuntimeException;

/**
 * Exception thrown when there is an error with the config file.
 *
 * @author  Brandon Olivares <b.olivares@inpsyde.com>
 */
final class ConfigFileException extends RuntimeException implements RestoreExceptionInterface
{
}
