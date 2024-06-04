<?php

declare(strict_types=1);

namespace Inpsyde\Restore\Api\Module\Session;

use Webmozart\Assert\Assert;

/**
 * Boilerplate for session handling by @dnaber.
 */
final class Session implements NotificableStorableSessionInterface
{
    /**
     * @var array<string, mixed>
     */
    private $session;

    /**
     * @param array<string, mixed> $session Reference to the $_SESSION
     */
    public function __construct(?array &$session)
    {
        if ($session !== null) {
            $this->session = &$session;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value): void
    {
        $this->session[$key] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        return $this->session[$key] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key): void
    {
        if (isset($this->session[$key])) {
            unset($this->session[$key]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function notifications(): array
    {
        $notifications = $this->get('notifications');
        if ($notifications === null) {
            return [];
        }

        Assert::isArray($notifications);
        Assert::allIsArray($notifications);
        Assert::allKeyExists($notifications, 'level');
        Assert::allKeyExists($notifications, 'msg');

        return array_filter($notifications, static function ($notification): bool {
            return !(
                !isset($notification['level']) || !is_string($notification['level'])
                || !isset($notification['msg']) || !is_string($notification['msg'])
            );
        });
    }

    public function info(string $message): void
    {
        $notifications = $this->get('notifications');
        if (!is_array($notifications)) {
            $notifications = [];
        }

        $notifications[] = [
            'level' => 'info',
            'msg' => $message,
        ];

        $this->set('notifications', $notifications);
    }

    public function warning(string $message): void
    {
        $notifications = $this->get('notifications');
        if (!is_array($notifications)) {
            $notifications = [];
        }

        $notifications[] = [
            'level' => 'warning',
            'msg' => $message,
        ];

        $this->set('notifications', $notifications);
    }

    public function success(string $message): void
    {
        $notifications = $this->get('notifications');
        if (!is_array($notifications)) {
            $notifications = [];
        }

        $notifications[] = [
            'level' => 'success',
            'msg' => $message,
        ];

        $this->set('notifications', $notifications);
    }

    public function error(string $message): void
    {
        $notifications = $this->get('notifications');
        if (!is_array($notifications)) {
            $notifications = [];
        }

        $notifications[] = [
            'level' => 'error',
            'msg' => $message,
        ];

        $this->set('notifications', $notifications);
    }

    /**
     * {@inheritdoc}
     */
    public function clean(): void
    {
        $this->session = [];
    }
}
