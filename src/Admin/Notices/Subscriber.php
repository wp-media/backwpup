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
	 * Constructor.
	 *
	 * @param AbstractNotice[] $notices Array of notice instances.
	 */
	public function __construct( array $notices ) {
		$this->notices = $notices;
	}

	/**
	 * Returns the events this subscriber wants to listen to.
	 *
	 * @return array
	 */
	public static function get_subscribed_events() {
		return [
			'all_admin_notices' => [ 'render_all_notices' ],
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
}
