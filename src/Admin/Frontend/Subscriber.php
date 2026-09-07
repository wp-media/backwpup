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
				[ 'add_delete_restore_files_script' ],
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
		$is_backwpup_page = ! empty( $screen_id ) && strpos( $screen_id, 'backwpup' ) !== false;

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
		$is_backwpup_onboarding_page = ! empty( $screen_id ) && strpos( $screen_id, 'backwpuponboarding' ) !== false;

		if ( ! $is_backwpup_onboarding_page ) {
			return;
		}

		$plugin_url  = $this->backwpup->get_plugin_data( 'URL' );
		$assets_path = $plugin_url . '/assets/js/backwpup-onboarding.js';

		wp_register_script( 'backwpup-onboarding-admin-js',  $assets_path,  [],  $this->backwpup->get_plugin_data( 'Version' ),  true );

		wp_enqueue_script( 'backwpup-onboarding-admin-js' );
	}

	/**
	 * Enqueue the delete-restore-files script on all admin pages.
	 *
	 * The stale-restore-files notice can appear on any admin screen, so the
	 * associated script must be available everywhere in the admin area.
	 *
	 * @since 5.7.3
	 *
	 * @return void
	 */
	public function add_delete_restore_files_script(): void {
		$plugin_url  = $this->backwpup->get_plugin_data( 'URL' );
		$assets_path = $plugin_url . '/assets/js/delete-restore-files.js';

		wp_enqueue_script(
			'backwpup-delete-restore-files',
			$assets_path,
			[],
			$this->backwpup->get_plugin_data( 'Version' ),
			true
		);
	}

	/**
	 * Handle redirection based on query parameters.
	 * If bwu_event is set, trigger Mixpanel event.
	 * Use bwu_event_property_{key} for event properties.
	 *
	 * @return void
	 */
	public function backwpup_redirect() {
		$destination = $this->resolve_redirect_destination();

		if ( '' === $destination ) {
			return;
		}

		Redirect::to( $destination );
		exit();
	}

	/**
	 * Works out where the current request wants to go.
	 *
	 * Fires the tracking event on the way, since that is the reason these links
	 * bounce through the admin at all. Returns an empty string when the request
	 * carries no redirect, and dies when the nonce does not match.
	 *
	 * @return string Destination to send the user to, empty when there is nothing to do.
	 */
	public function resolve_redirect_destination(): string {
		if ( ! isset( $_GET['bwu_redirect'] ) ) {
			return '';
		}

		// Keep the raw value around: the nonce action is built from it on both sides.
		$requested = (string) wp_unslash( $_GET['bwu_redirect'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized on the next line, nonce verified right after.
		$target    = sanitize_url( $requested );

		if ( ! filter_var( $target, FILTER_VALIDATE_URL ) ) {
			return '';
		}

		// Verify nonce for security. The action is bound to the destination.
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), Redirect::nonce_action( $requested ) ) ) {
			wp_die( esc_html__( 'Security check failed.', 'backwpup' ) );
		}

		$this->track_link_click();

		// Only our own hosts are reachable, anything else lands on the dashboard.
		$destination = Redirect::validate( $target );

		return '' === $destination ? admin_url() : $destination;
	}

	/**
	 * Forwards the bwu_event parameters to Mixpanel, when the link carries them.
	 *
	 * @return void
	 */
	private function track_link_click(): void {
		if ( ! isset( $_GET['bwu_event'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce already verified by the caller.
			return;
		}

		$event      = sanitize_text_field( wp_unslash( $_GET['bwu_event'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce already verified by the caller.
		$properties = [];

		foreach ( $_GET as $key => $value ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce already verified by the caller.
			if ( 0 === strpos( $key, 'bwu_event_property_' ) ) {
				$clean_key                = str_replace( 'bwu_event_property_', '', $key );
				$properties[ $clean_key ] = sanitize_text_field( wp_unslash( $value ) );
			}
		}

		do_action( 'backwpup_link_clicked', $event, $properties );
	}
}
