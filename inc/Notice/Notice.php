<?php

namespace Inpsyde\BackWPup\Notice;

abstract class Notice
{
    /**
     * @var string
     */
    public const ID = 'notice';
    /**
     * @var string
     */
    public const CAPABILITY = 'backwpup';

    /**
     * @var string
     */
    public const TYPE_ADMIN = 'admin';
    /**
     * @var string
     */
    public const TYPE_BACKWPUP = 'backwpup';

    /**
     * @var string
     */
    private const MAIN_ADMIN_PAGE_ID = 'toplevel_page_backwpup';
    /**
     * @var string
     */
    private const NETWORK_ADMIN_PAGE_ID = 'toplevel_page_backwpup-network';

    /**
     * @var string[]
     */
    protected static $main_admin_page_ids = [
        self::MAIN_ADMIN_PAGE_ID,
        self::NETWORK_ADMIN_PAGE_ID,
    ];

    /**
     * @var NoticeView
     */
    protected $view;

    /**
     * Whether this notice should be dismissible.
     *
     * @var bool
     */
    protected $dismissible = false;

    public function __construct(NoticeView $view, bool $dismissible = true)
    {
        $this->view = $view;
        $this->dismissible = $dismissible;
    }

    /**
     * Initialize.
     *
     * @param self::TYPE_* $type The notice type, either Notice::TYPE_ADMIN or Notice::TYPE_BACKWPUP.
     *                           Notice::TYPE_BACKWPUP makes the notice only visible on BackWPup pages.
     *                           Notice::TYPE_ADMIN makes the notice available on all WP admin pages.
     */
    public function init(string $type = self::TYPE_BACKWPUP): void
    {
        if (!is_admin()) {
            return;
        }
        if (!current_user_can(static::CAPABILITY)) {
            return;
        }
        if ($type === self::TYPE_BACKWPUP) {
            add_action('backwpup_admin_messages', function (): void {
                $this->notice();
            }, 20);
        } elseif ($type === static::TYPE_ADMIN) {
            add_action('admin_notices', function (): void {
                $this->notice();
            }, 20);
        } else {
            throw new \InvalidArgumentException(
                __('Invalid notice type specified', 'backwpup')
            );
        }

        if ($this->dismissible) {
            add_action('admin_enqueue_scripts', function (): void {
                $this->enqueueScripts();
            });
            DismissibleNoticeOption::setup_actions(true, static::ID, static::CAPABILITY);
        }
    }

    /**
     * Enqueue Scripts.
     */
    public function enqueueScripts(): void
    {
        $suffix = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';

        wp_enqueue_script(
            'backwpup-notice',
            untrailingslashit(\BackWPup::get_plugin_data('URL')) . sprintf('/assets/js/notice%s.js', $suffix),
            ['underscore', 'jquery'],
            (string) filemtime(untrailingslashit(\BackWPup::get_plugin_data('plugindir') . sprintf('/assets/js/notice%s.js', $suffix))),
            true
        );
    }

    /**
     * Print Notice.
     */
    public function notice(): void
    {
        if (!$this->isScreenAllowed()) {
            return;
        }
        if (!$this->shouldDisplay()) {
            return;
        }

        $this->render($this->message());
    }

    /**
     * Render the notice with the appropriate view type.
     *
     * This method can specify whether the notice should be a success, error,
     * warning, info, or generic notice.
     *
     * @param NoticeMessage $message The message to render
     */
    protected function render(NoticeMessage $message): void
    {
        $this->view->notice($message, $this->getDismissActionUrl());
    }

    /**
     * Gets the dismissible action URL from DismissibleNoticeOption.
     *
     * @return string|null The URL to dismiss the notice
     */
    protected function getDismissActionUrl(): ?string
    {
        if ($this->dismissible) {
            return DismissibleNoticeOption::dismiss_action_url(
                static::ID,
                DismissibleNoticeOption::FOR_USER_FOR_GOOD_ACTION
            );
        }

        return null;
    }

    /**
     * Return the message to display in the notice.
     *
     * @return NoticeMessage The message to display
     */
    abstract protected function message(): NoticeMessage;

    /**
     * Returns whether the current screen should show the notice.
     */
    protected function isScreenAllowed(): bool
    {
        $screen = get_current_screen();
        if (!$screen instanceof \WP_Screen) {
            return false;
        }

        $screen_id = $screen->id;

        return in_array($screen_id, static::$main_admin_page_ids, true);
    }

    /**
     * Whether to display the notice.
     */
    protected function shouldDisplay(): bool
    {
        if ($this->dismissible) {
            $option = new DismissibleNoticeOption(true);

            return !$option->is_dismissed(static::ID);
        }

        return true;
    }
}
