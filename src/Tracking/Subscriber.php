<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Tracking;

use WPMedia\BackWPup\EventManagement\SubscriberInterface;

class Subscriber implements SubscriberInterface {
	/**
	 * The tracking service.
	 *
	 * @var Tracking
	 */
	private $tracking;

	/**
	 * The Notices service.
	 *
	 * @var Notices
	 */
	private $notices;

	/**
	 * Constructor.
	 *
	 * @param Tracking $tracking The tracking service.
	 * @param Notices  $notices   The notices instance.
	 */
	public function __construct( Tracking $tracking, Notices $notices ) {
		$this->tracking = $tracking;
		$this->notices  = $notices;
	}

	/**
	 * Returns an array of events this subscriber wants to listen to.
	 *
	 * @return array
	 */
	public static function get_subscribed_events(): array {
		return [
			'backwpup_mixpanel_optin_changed' => 'track_optin_change',
			'backwpup_add_job'                => 'track_add_job',
			'backwpup_delete_job'             => 'track_delete_job',
			'backwpup_page_settings_save'     => 'update_setting',
			'wp_ajax_backwpup_notice_optin'   => 'notice_optin_callback',
			'admin_notices'                   => 'display_tracking_notice',
			'backwpup_create_job'             => [ 'track_start_job', 20, 2 ],
			'backwpup_track_end_job'          => [ 'track_end_job', 10, 2 ],
			'backwpup_beta_optin_change'      => 'track_beta_optin_change',
		];
	}

	/**
	 * Track the opt-in change event.
	 *
	 * @param bool $optin The new opt-in value.
	 *
	 * @return void
	 */
	public function track_optin_change( $optin ): void {
		$this->tracking->track_optin_change( $optin );
	}

	/**
	 * Track the beta opt-in change event.
	 *
	 * @param int $optin The new opt-in value.
	 *
	 * @return void
	 */
	public function track_beta_optin_change( $optin ): void {
		$this->tracking->track_beta_optin_change( $optin );
	}

	/**
	 * Track the addition of a new job.
	 *
	 * @param array $job The job data.
	 *
	 * @return void
	 */
	public function track_add_job( $job ): void {
		$this->tracking->track_add_job( $job );
	}

	/**
	 * Track the deletion of a job.
	 *
	 * @param int $job_id The ID of the job to delete.
	 *
	 * @return void
	 */
	public function track_delete_job( $job_id ): void {
		$this->tracking->track_delete_job( $job_id );
	}

	/**
	 * Update the Mixpanel opt-in setting.
	 *
	 * @return void
	 */
	public function update_setting(): void {
		$this->tracking->update_setting();
	}

	/**
	 * Handle the AJAX request for the opt-in notice.
	 *
	 * @return void
	 */
	public function notice_optin_callback(): void {
		$this->tracking->notice_optin_callback();
	}

	/**
	 * Display tracking notices
	 *
	 * @return void
	 */
	public function display_tracking_notice(): void {
		$this->notices->display_tracking_notices();
	}

	/**
	 * Track the start of a job.
	 *
	 * @param array  $job Current Job.
	 * @param string $filename Backup filename.
	 *
	 * @return void
	 */
	public function track_start_job( $job, $filename ) {
		$this->tracking->track_start_job( $job );
	}

	/**
	 * Track the end of a job.
	 *
	 * @param int   $job_id The ID of the job to delete.
	 * @param array $job_details The details of the job storages.
	 *
	 * @return void
	 */
	public function track_end_job( $job_id, array $job_details ) {
		$this->tracking->track_end_job( $job_id, $job_details );
	}
}
