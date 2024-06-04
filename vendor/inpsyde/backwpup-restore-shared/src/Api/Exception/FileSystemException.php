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

namespace Inpsyde\Restore\Api\Exception;

use RuntimeException;

/**
 * Class FileSystemException.
 *
 * @author Guido Scialfa <dev@guidoscialfa.com>
 */
class FileSystemException extends RuntimeException implements RestoreExceptionInterface
{
}
