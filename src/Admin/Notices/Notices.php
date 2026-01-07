<?php

namespace WPMedia\BackWPup\Admin\Notices;

use WPMedia\BackWPup\Adapters\BackWPupAdapter;
use WPMedia\BackWPup\Admin\Beacon\Beacon;
use WPMedia\BackWPup\Admin\OptionData;
use WPMedia\BackWPup\License\LicenseManager;
use WPMedia\BackWPup\Admin\Options\Options;

class Notices {

	private const VERSION = '5.5.0';
	/**
	 * Beacon instance.
	 *
	 * @var Beacon
	 */
	private $beacon;

	/**
	 * OptionData instance.
	 *
	 * @var OptionData
	 */
	private $options;

	/**
	 * Adapter for BackWPup plugin data.
	 *
	 * @var BackWPupAdapter
	 */
	private BackWPupAdapter $backwpup;

	/**
	 * Constructor
	 *
	 * @param OptionData      $options
	 * @param BackWPupAdapter $backwpup
	 * @param Beacon          $beacon An instance of Beacon.
	 */
	public function __construct( OptionData $options, BackWPupAdapter $backwpup, Beacon $beacon ) {
		$this->options  = $options;
		$this->backwpup = $backwpup;
		$this->beacon   = $beacon;
	}


	/**
	 * Display an update notice when the plugin is updated.
	 *
	 * @return void
	 */
	public function display_update_notices() {
		// Do not display if not on a BackWPup page or if already dismissed.
		if ( ! $this->should_display_notice() ) {
			return;
		}

		$current_version = $this->backwpup->get_plugin_data( 'Version' );

		// Do not display if the current version is greater/lesser than 5.5.0.
		if ( version_compare( $current_version, self::VERSION, '!=' ) ) {
			return;
		}

		$admin_notice = $this->beacon->get_suggest( 'file_format' );
		$message      = sprintf(
		// translators: %1$s: opening a tag, %2$s: closing a tag.
			__( 'You can now set the archive format and name for each backup for better flexibility and more control. We\'ve also added opt-in beta release among other improvements. Check out our %1$sblog post%2$s to learn more and see what’s coming next for BackWPup!',  'backwpup' ),
			'<a href="' . esc_url( $admin_notice['url'] ) . '" title="' . esc_attr( $admin_notice['title'] ) . '" target="_blank" rel="noopener noreferrer" class="text-primary-darker border-b border-primary-darker">',
			'</a>'
		);

		backwpup_notice_html(
			[
				'status'               => 'info',
				'dismissible'          => '',
				'title'                => sprintf(
					// translators: %1$s = strong opening tag, %2$s = strong closing tag.
					__( '📣 %1$sBackWPup 5.5 is here! %2$s', 'backwpup' ),
					'<strong>',
					'</strong>',
				),
				'message'              => $message,
				'dismiss_button'       => 'backwpup_update_notice',
				'dismiss_button_class' => 'bwpup-ajax-close',
				'id'                   => 'backwpup_update_notice',
			]
		);
	}

	/**
	 * Display license notice if license is not valid.
	 * This notice is only shown to pro users.
	 * This notice is not shown during onboarding.
	 * This notice is not dismissible.
	 *
	 * @return void
	 */
	public function display_license_notice() {
		if ( ! \BackWPup::is_pro() ) {
			return;
		}
		// Check if onboarding finished.
		$is_onboarding = get_site_option( 'backwpup_onboarding', false );
		if ( $is_onboarding ) {
			return;
		}
		// Check license status.
		$license_status = get_site_option( LicenseManager::LICENSE_STATUS, 'inactive' );
		if ( 'active' === $license_status ) {
			return;
		}

		$notice_data = $this->get_license_notice_data();
		backwpup_notice_html(
			[
				'status'         => 'warning',
				'dismissible'    => '',
				'title'          => $notice_data['title'],
				'message'        => $notice_data['message'],
				'dismiss_button' => false,
				'id'             => 'backwpup_license_notice',
			]
		);
	}

	/**
	 * Ajax callback to save the dismiss as a user meta
	 *
	 * @since 5.4
	 *
	 * @return void
	 */
	public function backwpup_dismiss_notices() {
		$args = ! empty( $_GET ) ? $_GET : []; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! isset( $args['box'], $args['action'], $args['_wpnonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $args['_wpnonce'], "{$args['action']}_{$args['box']}" ) ) {
			return;
		}

		$actual = get_user_meta( get_current_user_id(), 'backwpup_notification_boxes', true );
		$actual = array_merge( (array) $actual, [ $args['box'] ] );
		$actual = array_filter( $actual );
		$actual = array_unique( $actual );

		update_user_meta( get_current_user_id(), 'backwpup_notification_boxes', $actual );
		delete_transient( $args['box'] );

		wp_send_json_success();
	}

	/**
	 * Checks if the update notice should be displayed
	 *
	 * @since 5.4
	 *
	 * @return boolean
	 */
	private function should_display_notice(): bool {
		$screen = get_current_screen();
		if ( isset( $screen->id ) && ! str_contains( $screen->id, 'backwpup' ) ) {
			return false;
		}

		$boxes = get_user_meta( get_current_user_id(), 'backwpup_notification_boxes', true );

		if ( in_array( 'backwpup_update_notice', (array) $boxes, true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get license notice data based on license status
	 *
	 * @return array
	 */
	protected function get_license_notice_data(): array {
		// Check if legacy payment method is used. and set notice data accordingly.
		if ( true === (bool) get_site_option( LicenseManager::LICENSE_LEGACY_PAYMENT_METHOD, false ) ) {
			$update_payment_method_link = $this->beacon->get_suggest(
				'update-payment-method',
				true,
				[
					'bwu_event' => 'legacy_update_payment_method',
				]
			);
			$title                      = sprintf(
				// translators: %1$s = strong opening tag, %2$s = link opening tag, %3$s = link closing tag, %4$s = strong closing tag.
				__( '⚠️ %1$sAction Required – %2$sUpdate Your Payment Method%3$s%4$s', 'backwpup' ),
				'<strong>',
				'<a href="' . esc_url( $update_payment_method_link['url'] ) . '" title="' . esc_attr( $update_payment_method_link['title'] ) . '" target="_blank" class="text-primary-darker border-b border-primary-darker">',
				'</a>',
				'</strong>'
			);
			$message = __( 'Your payment method is outdated. Update it to restore your Pro features.', 'backwpup' );
		} else {
			$update_payment_method_link = $this->beacon->get_suggest(
				'update-payment-method',
				true,
				[
					'bwu_event' => 'expired_license_update_payment_method',
				]
			);
			$title                      = sprintf(
				// translators: %1$s = strong opening tag, %2$s = strong closing tag.
				__( '⚠️ %1$sYour BackWPup Pro Plan Has Expired%2$s', 'backwpup' ),
				'<strong>',
				'</strong>'
			);
			$message = sprintf(
				// translators: %1$s = link opening tag, %2$s = link closing tag.
				__( '%1$sRenew now%2$s to continue receiving updates and Pro features.', 'backwpup' ),
				'<a href="' . esc_url( $update_payment_method_link['url'] ) . '" title="' . esc_attr( $update_payment_method_link['title'] ) . '" target="_blank" class="text-primary-darker border-b border-primary-darker">',
				'</a>'
			);
		}
		return [
			'title'   => $title,
			'message' => $message,
		];
	}
}
