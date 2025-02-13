<?php

declare(strict_types=1);

/**
 * Registry Exception.
 *
 * @since   1.0.0
 */

namespace Inpsyde\Restore\Api\Module;

use Inpsyde\Restore\Api\Exception\RestoreExceptionInterface;

final class RegistryException extends \Exception implements RestoreExceptionInterface
{
}
