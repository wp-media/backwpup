<?php

namespace WPMedia\BackWPup\Admin\Notices;

use WPMedia\BackWPup\Adapters\BackWPupAdapter;
use WPMedia\BackWPup\Admin\Beacon\Beacon;
use WPMedia\BackWPup\Admin\OptionData;
use WPMedia\BackWPup\License\LicenseStateProviderInterface;
use WPMedia\BackWPup\License\PaymentMethodProviderInterface;

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
	 * Provides the current license state.
	 *
	 * @var LicenseStateProviderInterface|null
	 */
	private ?LicenseStateProviderInterface $license_state_provider;

	/**
	 * Provides information about the payment method.
	 *
	 * @var PaymentMethodProviderInterface|null
	 */
	private ?PaymentMethodProviderInterface $payment_method_provider;

	/**
	 * Factory to build license notice content.
	 *
	 * @var LicenseNoticeFactory|null
	 */
	private ?LicenseNoticeFactory $license_notice_factory;

	/**
	 * Constructor
	 *
	 * @param OptionData                          $options OptionData instance.
	 * @param BackWPupAdapter                     $backwpup $backwpup Adapter for BackWPup plugin data.
	 * @param Beacon                              $beacon An instance of Beacon.
	 * @param LicenseStateProviderInterface|null  $license_state_provider Provides the current license state.
	 * @param PaymentMethodProviderInterface|null $payment_method_provider Provides payment method information (legacy vs current).
	 * @param LicenseNoticeFactory|null           $license_notice_factory Factory to build license notice title and message.
	 */
	public function __construct(
		OptionData $options,
		BackWPupAdapter $backwpup,
		Beacon $beacon,
		$license_state_provider = null,
		$payment_method_provider = null,
		$license_notice_factory = null
	) {
		$this->options                 = $options;
		$this->backwpup                = $backwpup;
		$this->beacon                  = $beacon;
		$this->license_state_provider  = $license_state_provider instanceof LicenseStateProviderInterface
			? $license_state_provider
			: null;
		$this->payment_method_provider = $payment_method_provider instanceof PaymentMethodProviderInterface
			? $payment_method_provider
			: null;
		$this->license_notice_factory  = $license_notice_factory instanceof LicenseNoticeFactory
			? $license_notice_factory
			: null;
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
		if (
			! $this->license_state_provider instanceof LicenseStateProviderInterface
			|| ! $this->payment_method_provider instanceof PaymentMethodProviderInterface
			|| ! $this->license_notice_factory instanceof LicenseNoticeFactory
		) {
			return;
		}
		// Check license status.
		$state = $this->license_state_provider->get_state();
		if ( $state->is_active() ) {
			return;
		}

		$notice_data = $this->license_notice_factory->build(
			$state,
			$this->payment_method_provider->is_legacy(),
			$this->is_backwpup_main_screen()
		);

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
		if ( ! $this->is_backwpup_screen() ) {
			return false;
		}

		$boxes = get_user_meta( get_current_user_id(), 'backwpup_notification_boxes', true );

		if ( in_array( 'backwpup_update_notice', (array) $boxes, true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Determines whether the current screen belongs to BackWPup admin pages.
	 *
	 * @return bool
	 */
	private function is_backwpup_screen(): bool {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! is_object( $screen ) || ! isset( $screen->id ) ) {
			return false;
		}

		return false !== strpos( (string) $screen->id, 'backwpup' );
	}

	/**
	 * Determines whether the current screen is the main BackWPup admin page.
	 *
	 * @return bool
	 */
	private function is_backwpup_main_screen(): bool {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! is_object( $screen ) || ! isset( $screen->id ) ) {
			return false;
		}

		return in_array(
			(string) $screen->id,
			[
				'toplevel_page_backwpup',
				'toplevel_page_backwpup-network',
			],
			true
		);
	}
}
