<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Admin\Notices;

use WPMedia\BackWPup\EventManagement\SubscriberInterface;
use WPMedia\BackWPup\Admin\Notices\Notices\AbstractNotice;

/**
 * Subscriber class responsible for rendering admin notices.
 */
class Subscriber implements SubscriberInterface {
	/**
	 * Array of notice instances to be rendered.
	 *
	 * @var AbstractNotice[]
	 */
	private array $notices;

	/**
	 * Notices instance.
	 *
	 * @var Notices
	 */
	private Notices $admin_notices;

	/**
	 * Array of banner instances to be rendered.
	 *
	 * @var AbstractNotice[]
	 */
	private array $banners;

	/**
	 * Constructor.
	 *
	 * @param Notices          $admin_notices The Notices instance.
	 * @param AbstractNotice[] $notices Array of notice instances.
	 * @param AbstractNotice[] $banners Array of banner instances.
	 */
	public function __construct( Notices $admin_notices, $notices, $banners ) {
		$this->notices       = $notices;
		$this->banners       = $banners;
		$this->admin_notices = $admin_notices;
	}

	/**
	 * Returns the events this subscriber wants to listen to.
	 *
	 * @return array
	 */
	public static function get_subscribed_events() {
		return [
			'all_admin_notices'                  => [
				[ 'render_all_notices' ],
				[ 'display_update_notice' ],
				[ 'display_license_notice' ],
			],
			'backwpup_banners'                   => 'render_banners',
			'wp_ajax_backwpup_dismiss_notice'    => 'backwpup_dismiss_notices',
			'admin_post_backwpup_dismiss_notice' => 'backwpup_dismiss_notices',
		];
	}

	/**
	 * Renders all registered notices on the admin_notices hook.
	 *
	 * @return void
	 */
	public function render_all_notices() {
		foreach ( $this->notices as $notice ) {
			$notice->maybe_render();
		}
	}

	/**
	 * Renders banners on the backwpup_custom_notices hook.
	 *
	 * @return void
	 */
	public function render_banners() {
		foreach ( $this->banners as $banner ) {
			$banner->maybe_render();
		}
	}

	/**
	 * Display updates notices.
	 *
	 * @return void
	 */
	public function display_update_notice(): void {
		$this->admin_notices->display_update_notices();
	}

	/**
	 * Display license notice.
	 *
	 * @return void
	 */
	public function display_license_notice(): void {
		$this->admin_notices->display_license_notice();
	}

	/**
	 * Dismiss notice update.
	 *
	 * @return void
	 */
	public function backwpup_dismiss_notices(): void {
		$this->admin_notices->backwpup_dismiss_notices();
	}
}
