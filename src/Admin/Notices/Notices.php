<?php

namespace WPMedia\BackWPup\Admin\Notices;

use WPMedia\BackWPup\Adapters\BackWPupAdapter;
use WPMedia\BackWPup\Admin\Beacon\Beacon;
use WPMedia\BackWPup\Admin\OptionData;
use WPMedia\BackWPup\Admin\Options\Options;

class Notices {

	private const VERSION = '5.4.0';
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

		// Do not display if the current version is greater/lesser than 5.4.0.
		if ( version_compare( $current_version, self::VERSION, '!=' ) ) {
			return;
		}

		$include_extra_files = $this->beacon->get_suggest( 'include_extra_files' );
		$message             = sprintf(
		// translators: %1$s: opening a tag, %2$s: closing a tag.
			__( 'You can now include non-WordPress files and folders directly in your backups! Simply select the files or folders you want to add to your scheduled or manual backups. This release also brings several enhancements and bug fixes. Check out our %1$sblog post%2$s to learn more and see what’s coming next for BackWPup!', 'backwpup' ),
			'<a href="' . esc_url( $include_extra_files['url'] ) . '" title="' . esc_attr( $include_extra_files['title'] ) . '" target="_blank" rel="noopener noreferrer" class="text-primary-darker border-b border-primary-darker">',
			'</a>'
		);

		backwpup_notice_html(
			[
				'status'               => 'info',
				'dismissible'          => '',
				'title'                => sprintf(
					// translators: %1$s = strong opening tag, %2$s = strong closing tag.
					__( '🎉 %1$sWelcome to BackWPup 5.4! %2$s', 'backwpup' ),
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
}
