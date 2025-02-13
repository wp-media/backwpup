<?php

declare(strict_types=1);

namespace Inpsyde\Restore\Api\Module\Session;

/**
 * @psalm-type Notification=array{level: string, msg: string}
 */
interface NotificationInterface
{
    /**
     * Wrapper method to add an info.
     *
     * @param string $message Information for user
     */
    public function info(string $message): void;

    /**
     * Wrapper method to add a warning message.
     *
     * @param string $message Warning for user
     */
    public function warning(string $message): void;

    /**
     * Wrapper method to add a success message.
     *
     * @param string $message Success message for user
     */
    public function success(string $message): void;

    /**
     * Wrapper method to add an error message.
     *
     * @param string $message Error message for user
     */
    public function error(string $message): void;

    /**
     * Getter method to retrieve notifications.
     *
     * @return array<Notification>
     */
    public function notifications(): array;

    /**
     * Clean all notifications.
     */
    public function clean(): void;
}
