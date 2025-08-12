<?php

namespace WPMedia\BackWPup\Admin\Notices;

use WPMedia\BackWPup\Adapters\BackWPupAdapter;
use WPMedia\BackWPup\Admin\Beacon\Beacon;
use WPMedia\BackWPup\Admin\OptionData;
use WPMedia\BackWPup\Admin\Options\Options;

class Notices {
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
	 */
	public function __construct( OptionData $options, BackWPupAdapter $backwpup ) {
		$this->options  = $options;
		$this->backwpup = $backwpup;
	}


	/**
	 * Display an update notice when the plugin is updated.
	 *
	 * @return void
	 */
	public function display_update_notices() {
		if ( ! $this->should_display_notice() ) {
			return;
		}

		$current_version = $this->backwpup->get_plugin_data( 'Version' );

		// Bail-out if current version is greater than or equal to 5.3.1.
		if ( version_compare( $current_version, '5.3.1', '>=' ) ) {
			return;
		}

		backwpup_notice_html(
			[
				'status'               => 'info',
				'dismissible'          => '',
				'title'                => sprintf(
					// translators: %1$s = strong opening tag, %2$s = strong closing tag.
					__( '🎉 %1$sBackWPup 5.3 is here! %2$s', 'backwpup' ),
					'<strong>',
					'</strong>',
				),
				'message'              => __( 'You can now back up Files & Database together in a single backup and schedule it. We’ve also fixed new backups not respecting your selected archive format, along with other improvements.', 'backwpup' ),
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
