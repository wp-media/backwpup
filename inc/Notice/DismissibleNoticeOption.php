<?php

namespace Inpsyde\BackWPup\Notice;

class DismissibleNoticeOption
{
    /**
     * @var string
     */
    public const OPTION_PREFIX = 'backwpup_dinotopt_';
    /**
     * @var string
     */
    public const FOR_GOOD_ACTION = 'dismiss_admin_notice_for_good';
    /**
     * @var string
     */
    public const FOR_NOW_ACTION = 'dismiss_admin_notice_for_now';
    /**
     * @var string
     */
    public const FOR_USER_FOR_GOOD_ACTION = 'dismiss_admin_notice_for_good_user';
    /**
     * @var string
     */
    public const SKIP = 'skip_action';

    /**
     * @var string[]
     */
    private const ALL_ACTIONS = [
        self::FOR_GOOD_ACTION,
        self::FOR_NOW_ACTION,
        self::FOR_USER_FOR_GOOD_ACTION,
    ];

    /**
     * @phpstan-var array{
     *     sitewide: array<string, string>,
     *     blog: array<string, string>,
     * }
     */
    private static $setup = [
        'sitewide' => [],
        'blog' => [],
    ];

    /**
     * @var bool
     */
    private $sitewide = false;

    public function __construct(bool $sitewide = false)
    {
        $this->sitewide = $sitewide;
    }

    public static function setup_actions(bool $sitewide, string $notice_id, string $capability = 'read'): void
    {
        if (!is_string($notice_id)) {
            return;
        }

        $sitewide = $sitewide && is_multisite();

        $key = $sitewide ? 'sitewide' : 'blog';

        if (array_key_exists($notice_id, self::$setup[$key])) {
            return;
        }

        if (self::$setup[$key] === []) {
            $option = new self($sitewide);
            add_action('admin_post_' . self::FOR_GOOD_ACTION, function () use ($option): void {
                $option->dismiss();
            });
            add_action('admin_post_' . self::FOR_NOW_ACTION, function () use ($option): void {
                $option->dismiss();
            });
            add_action('admin_post_' . self::FOR_USER_FOR_GOOD_ACTION, function () use ($option): void {
                $option->dismiss();
            });
        }

        self::$setup[$key][$notice_id] = $capability;
    }

    /**
     * Returns the URL that can be used to dismiss a given notice for good or temporarily according to given action.
     */
    public static function dismiss_action_url(string $notice_id, string $action): string
    {
        return add_query_arg(
            [
                'action' => $action,
                'notice' => $notice_id,
                'blog' => get_current_blog_id(),
                $action => wp_create_nonce($action),
            ],
            admin_url('admin-post.php')
        );
    }

    /**
     * Returns true when given notice is dismissed for good or temporarily for current user.
     */
    public function is_dismissed(string $notice_id): bool
    {
        $option_name = self::OPTION_PREFIX . $notice_id;

        // Dismissed for good?
        $option = $this->sitewide ? get_site_option($option_name) : get_option($option_name);
        if ($option) {
            return true;
        }

        // Dismissed for good for user?
        if (get_user_option($option_name)) {
            return true;
        }

        // Dismissed for now for user?
        $transient_name = self::OPTION_PREFIX . $notice_id . get_current_user_id();
        $transient = $this->sitewide ? get_site_transient($transient_name) : get_transient($transient_name);

        return (bool) $transient;
    }

    /**
     * Action callback to dismiss an action for good.
     */
    public function dismiss(): void
    {
        [$action, $notice_id, $is_ajax] = $this->assert_allowed();

        $end_request = true;

        switch ($action) {
            case self::FOR_GOOD_ACTION:
                $this->dismiss_for_good($notice_id);
                break;

            case self::FOR_USER_FOR_GOOD_ACTION:
                $this->dismiss_for_user_for_good($notice_id);
                break;

            case self::FOR_NOW_ACTION:
                $this->dismiss_for_now($notice_id);
                break;

            case self::SKIP:
                $end_request = false;
                break;
        }

        if ($end_request) {
            $this->end_request($is_ajax);
        }
    }

    /**
     * Action callback to dismiss an action for good.
     */
    private function dismiss_for_good(string $notice_id): void
    {
        $option_name = self::OPTION_PREFIX . $notice_id;

        $this->sitewide
            ? update_site_option($option_name, 1)
            : update_option($option_name, 1, false);
    }

    /**
     * Action callback to dismiss an action definitively for current user.
     */
    private function dismiss_for_user_for_good(string $notice_id): void
    {
        update_user_option(
            get_current_user_id(),
            self::OPTION_PREFIX . $notice_id,
            1,
            $this->sitewide
        );
    }

    /**
     * Action callback to dismiss an action temporarily for current user.
     */
    private function dismiss_for_now(string $notice_id): void
    {
        $transient_name = self::OPTION_PREFIX . $notice_id . get_current_user_id();
        $expiration = 12 * HOUR_IN_SECONDS;

        $this->sitewide
            ? set_site_transient($transient_name, 1, $expiration)
            : set_transient($transient_name, 1, $expiration);
    }

    /**
     * Ends a request redirecting to referer page.
     */
    private function end_request(bool $no_redirect = false): void
    {
        if ($no_redirect) {
            exit();
        }

        $referer = wp_get_raw_referer();
        if (!$referer) {
            $referer = $this->sitewide && is_super_admin() ? network_admin_url() : admin_url();
        }

        wp_safe_redirect($referer);

        exit();
    }

    /**
     * @phpstan-return array{string, string, bool}
     */
    private function assert_allowed(): array
    {
        if (!is_admin()) {
            $this->end_request();
        }

        $definition = [
            'action' => FILTER_SANITIZE_STRING,
            'notice' => FILTER_SANITIZE_STRING,
            'blog' => FILTER_SANITIZE_NUMBER_INT,
            'isAjax' => FILTER_VALIDATE_BOOLEAN,
        ];

        $data = array_merge(
            array_filter((array) filter_input_array(INPUT_GET, $definition)),
            array_filter((array) filter_input_array(INPUT_POST, $definition))
        );

        $is_ajax = !empty($data['isAjax']);
        $action = empty($data['action']) ? '' : $data['action'];
        $notice = empty($data['notice']) ? '' : $data['notice'];

        if (!$action
            || !$notice
            || !is_string($notice)
            || !in_array($action, self::ALL_ACTIONS, true)
        ) {
            $this->end_request($is_ajax);
        }

        $key = $this->sitewide ? 'sitewide' : 'blog';
        $swap_key = $this->sitewide ? 'blog' : 'sitewide';
        $capability = empty(self::$setup[$key][$notice]) ? '' : self::$setup[$key][$notice];

        if (!$capability && !empty(self::$setup[$swap_key][$notice])) {
            return [self::SKIP, '', $is_ajax];
        }

        if (!$capability || !current_user_can($capability)) {
            $this->end_request($is_ajax);
        }
        $nonce = filter_input(INPUT_POST, $action, FILTER_SANITIZE_STRING)
            ?: filter_input(INPUT_GET, $action, FILTER_SANITIZE_STRING);

        if (!is_string($nonce) || !wp_verify_nonce($nonce, $action)) {
            $this->end_request($is_ajax);
        }

        if (!$this->sitewide
            && (empty($data['blog']) || get_current_blog_id() !== (int) $data['blog'])
        ) {
            $this->end_request($is_ajax);
        }

        return [$action, $notice, $is_ajax];
    }
}
