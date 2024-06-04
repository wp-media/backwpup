<?php

declare(strict_types=1);

namespace Inpsyde\Restore\Api\Module\Session;

use Inpsyde\Restore\StorageInterface;

interface NotificableStorableSessionInterface extends NotificationInterface, StorageInterface
{
}
