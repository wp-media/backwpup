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
			'admin_init'            => [
				[ 'backwpup_redirect' ],
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
		$screen_id        = isset( $screen->id ) ? (string) $screen->id : '';
		$is_backwpup_page = ! empty( $screen_id ) && str_contains( $screen_id, 'backwpup' );

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
		$screen_id                   = isset( $screen->id ) ? (string) $screen->id : '';
		$is_backwpup_onboarding_page = ! empty( $screen_id ) && str_contains( $screen_id, 'backwpuponboarding' );

		if ( ! $is_backwpup_onboarding_page ) {
			return;
		}

		$plugin_url  = $this->backwpup->get_plugin_data( 'URL' );
		$assets_path = $plugin_url . '/assets/js/backwpup-onboarding.js';

		wp_register_script( 'backwpup-onboarding-admin-js',  $assets_path,  [],  $this->backwpup->get_plugin_data( 'Version' ),  true );

		wp_enqueue_script( 'backwpup-onboarding-admin-js' );
	}

	/**
	 * Handle redirection based on query parameters.
	 * If bwu_event is set, trigger Mixpanel event.
	 * Use bwu_event_property_{key} for event properties.
	 *
	 * @return void
	 */
	public function backwpup_redirect() {
		if ( isset( $_GET['bwu_redirect'] ) && filter_var( wp_unslash( $_GET['bwu_redirect'] ), FILTER_VALIDATE_URL ) ) {
			// Verify nonce for security.
			if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'backwpup_redirect_nonce' ) ) {
				wp_die( esc_html__( 'Security check failed.', 'backwpup' ) );
			}
			// If event is set, trigger it.
			if ( isset( $_GET['bwu_event'] ) ) {
				$event      = sanitize_text_field( wp_unslash( $_GET['bwu_event'] ) );
				$properties = [];
				foreach ( $_GET as $key => $value ) {
					if ( 0 === strpos( $key, 'bwu_event_property_' ) ) {
						$clean_key                = str_replace( 'bwu_event_property_', '', $key );
						$properties[ $clean_key ] = sanitize_text_field( wp_unslash( $value ) );
					}
				}
				// Trigger Mixpanel event.
				do_action( 'backwpup_link_clicked', $event, $properties );
			}

			wp_redirect( sanitize_url( wp_unslash( $_GET['bwu_redirect'] ) ) ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
			exit();
		}
	}
}
