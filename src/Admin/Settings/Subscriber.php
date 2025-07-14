<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Admin\Settings;

use BackWPup_Cron;
use BackWPup_Option;
use WPMedia\BackWPup\EventManagement\SubscriberInterface;

class Subscriber implements SubscriberInterface {
	/**
	 * Returns an array of events that this subscriber wants to listen to.
	 *
	 * @return mixed
	 */
	public static function get_subscribed_events() {
		return [
			'backwpup_page_jobs_get_bulk_actions' => [ 'get_bulk_actions' ],
			'backwpup_page_jobs_load'             => [
				[ 'process_link_action' ],
				[ 'process_wpcron_action' ],
			],
			'backwpup_save_archiveformat'         => [ 'save_archive_format' ],
		];
	}

	/**
	 * List page bulk actions.
	 *
	 * @param array $actions The array of actions.
	 *
	 * @return array
	 */
	public function get_bulk_actions( array $actions ): array {
		$actions['wpcron'] = __( 'Activate with CRON', 'backwpup' );
		$actions['link']   = __( 'Activate with Link', 'backwpup' );

		return $actions;
	}

	/**
	 * Process link action request.
	 *
	 * @param string $action The action to process.
	 *
	 * @return void
	 */
	public function process_link_action( string $action ): void {
		// Bail early.
		if ( 'link' !== $action ) {
			return;
		}
		$jobs = isset( $_GET['jobs'] ) ? $_GET['jobs'] : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.NonceVerification

		$this->update_jobs( $jobs, $action );
	}

	/**
	 * Process wpcron action request.
	 *
	 * @param string $action The action to process.
	 *
	 * @return void
	 */
	public function process_wpcron_action( string $action ): void {
		// Bail early.
		if ( 'wpcron' !== $action ) {
			return;
		}

		$jobs = isset( $_GET['jobs'] ) ? $_GET['jobs'] : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.NonceVerification
		$this->update_jobs( $jobs, $action );
	}



	/**
	 * Update jobs active type
	 *
	 * @param array  $jobs Array of jobs to update.
	 * @param string $value The value to update the job details to.
	 *
	 * @return void
	 */
	private function update_jobs( array $jobs, string $value ): void {

		check_admin_referer( 'bulk-jobs' );

		if ( empty( $jobs ) ) {
			return;
		}

		$jobs  = array_map( 'absint', $jobs );
		$value = sanitize_text_field( wp_unslash( $value ) );

		foreach ( $jobs as $job_id ) {
			BackWPup_Option::update( $job_id, 'activetype', $value );
			// Update schedule for wpcron type of job.
			if ( 'wpcron' === $value ) {
				$job = BackWPup_Option::get_job( $job_id );

				if ( $job ) {
					wp_schedule_single_event(
						BackWPup_Cron::cron_next( $job['cron'] ),
						'backwpup_cron',
						[
							'arg' => $job_id,
						]
					);
				}
			}
		}
	}

	/**
	 * Save archive format.
	 *
	 * @param string $archive_format The archive format to save.
	 *
	 * @return void
	 */
	public function save_archive_format( $archive_format ): void {
		// Save archive format general value.
		update_site_option( 'backwpup_archiveformat', $archive_format );
	}
}
