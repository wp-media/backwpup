<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Admin\Frontend;

use WPMedia\BackWPup\Adapters\BackWPupAdapter;
use WPMedia\BackWPup\EventManagement\SubscriberInterface;

class Subscriber implements SubscriberInterface {
	/**
	 * Adapter for BackWPup plugin data.
	 *
	 * @var BackWPupAdapter
	 */
	private BackWPupAdapter $backwpup;

	/**
	 * Constructor.
	 *
	 * @param BackWPupAdapter $backwpup Backwpup Adapter instance.
	 */
	public function __construct( BackWPupAdapter $backwpup ) {
		$this->backwpup = $backwpup;
	}
	/**
	 * Returns the events this subscriber wants to listen to.
	 *
	 * @return array
	 */
	public static function get_subscribed_events() {
		return [
			'admin_enqueue_scripts' => [
				[ 'add_backwpup_job_script' ],
				[ 'add_backwpup_onboarding_script' ],
			],
		];
	}

	/**
	 * Add script to the footer of the admin page for backwpup pages only.
	 *
	 * @since 5.3
	 *
	 * @return void
	 */
	public function add_backwpup_job_script() {
		$screen           = get_current_screen();
		$is_backwpup_page = isset( $screen->id ) && str_contains( $screen->id, 'backwpup' );

		if ( ! $is_backwpup_page ) {
			return;
		}

		$plugin_url  = $this->backwpup->get_plugin_data( 'URL' );
		$assets_path = $plugin_url . '/assets/js/backwpup-job.js';

		wp_register_script( 'backwpup-job-admin-js',  $assets_path,  [],  $this->backwpup->get_plugin_data( 'Version' ),  true );

		wp_enqueue_script( 'backwpup-job-admin-js' );
	}

	/**
	 * Add script to the footer of the admin page for backwpup onboarding pages only.
	 *
	 * @since 5.3.1
	 *
	 * @return void
	 */
	public function add_backwpup_onboarding_script() {
		$screen                      = get_current_screen();
		$is_backwpup_onboarding_page = isset( $screen->id ) && str_contains( $screen->id, 'backwpuponboarding' );

		if ( ! $is_backwpup_onboarding_page ) {
			return;
		}

		$plugin_url  = $this->backwpup->get_plugin_data( 'URL' );
		$assets_path = $plugin_url . '/assets/js/backwpup-onboarding.js';

		wp_register_script( 'backwpup-onboarding-admin-js',  $assets_path,  [],  $this->backwpup->get_plugin_data( 'Version' ),  true );

		wp_enqueue_script( 'backwpup-onboarding-admin-js' );
	}
}
