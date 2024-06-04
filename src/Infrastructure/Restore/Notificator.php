<?php
/**
 * Restore Notifications.
 */

declare(strict_types=1);

namespace Inpsyde\BackWPup\Infrastructure\Restore;

use function add_action;
use Inpsyde\Restore\Api\Module\Session\NotificableStorableSessionInterface;
use Inpsyde\Restore\Api\Module\Session\Session;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class Notifications.
 *
 * @since
 */
final class Notificator
{
    /**
     * Session.
     *
     * @var NotificableStorableSessionInterface The session to use to retrieve the messages
     */
    private $session;

    /**
     * Translator.
     *
     * @var TranslatorInterface The translator for strings
     */
    private $translator;

    /**
     * List of notifications.
     *
     * @var array<string, string[]> The container for the notifications message
     */
    private $notifications = [];

    /**
     * Notificator constructor.
     *
     * @param NotificableStorableSessionInterface $session    the session to use
     *                                                        to retrieve the messages
     * @param TranslatorInterface                 $translator the translator for strings
     */
    public function __construct(NotificableStorableSessionInterface $session, TranslatorInterface $translator)
    {
        $this->session = $session;
        $this->translator = $translator;
    }

    /**
     * Load.
     *
     * Set the hooks
     */
    public function load(): void
    {
        add_action(
            (is_network_admin() ? 'network_admin_notices' : 'admin_notices'),
            [$this, 'notify']
        );
    }

    /**
     * Notify.
     *
     * Load the template and show the notifications
     */
    public function notify(): void
    {
        $this->setNotifications();

        $this->notifications && backwpup_template( // phpcs:ignore
            (object) [
                'notifies' => $this->notifications,
            ],
            '/restore/notifications.php'
        );
    }

    /**
     * Set Notifications.
     */
    private function setNotifications(): void
    {
        foreach ($this->session->notifications() as $note) {
            // Create a new item if not exists.
            if (!isset($this->notifications[$note['level']])) {
                $this->notifications[$note['level']] = [];
            }

            // Set the message and translate it.
            // Don't use WordPress functions here because the text messages come from the shared library.
            $this->notifications[$note['level']][] = $this->translator->trans($note['msg']);
        }

        // Clean the session.
        $this->session->delete('notifications');
    }
}
