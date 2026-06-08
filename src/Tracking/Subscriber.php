<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Tracking;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
	 * The nudge tracking service.
	 *
	 * @var NudgeTracking
	 */
	private $nudge_tracking;

	/**
	 * Constructor.
	 *
	 * @param Tracking      $tracking       The tracking service.
	 * @param Notices       $notices        The notices instance.
	 * @param NudgeTracking $nudge_tracking The nudge tracking service.
	 */
	public function __construct( Tracking $tracking, Notices $notices, NudgeTracking $nudge_tracking ) {
		$this->tracking       = $tracking;
		$this->notices        = $notices;
		$this->nudge_tracking = $nudge_tracking;
	}

	/**
	 * Returns an array of events this subscriber wants to listen to.
	 *
	 * @return array
	 */
	public static function get_subscribed_events(): array {
		return [
			'backwpup_mixpanel_optin_changed'              => 'track_optin_change',
			'backwpup_add_job'                             => 'track_add_job',
			'backwpup_delete_job'                          => 'track_delete_job',
			'backwpup_page_settings_save'                  => 'update_setting',
			'wp_ajax_backwpup_notice_optin'                => 'notice_optin_callback',
			'admin_notices'                                => 'display_tracking_notice',
			'backwpup_create_job'                          => [ 'track_start_job', 20, 3 ],
			'backwpup_track_end_job'                       => [ 'track_end_job', 10, 3 ],
			'backwpup_beta_optin_change'                   => 'track_beta_optin_change',
			'backwpup_link_clicked'                        => [ 'track_link_clicked', 10, 2 ],
			'backwpup_track_support_tool_button_displayed' => 'track_support_tool_button_displayed',
			'backwpup_track_support_tool_button_clicked'   => 'track_support_tool_button_clicked',
			'backwpup_track_expired_banner_shown'          => 'track_expired_banner_shown',
			'backwpup_track_log_opened'                    => [ 'track_log_opened', 10, 4 ],
			'backwpup_track_dashboard_viewed'              => 'track_dashboard_viewed',
			'backwpup_track_nudge_impression'              => [ 'track_nudge_impression', 10, 1 ],
			'backwpup_track_nudge_click'                   => [ 'track_nudge_click', 10, 1 ],
			'backwpup_track_locked_option_click'           => [ 'track_locked_option_click', 10, 1 ],
			'wp_ajax_backwpup_track_nudge'                 => 'ajax_track_nudge',
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
	 * @param string $trigger Backup trigger.
	 *
	 * @return void
	 */
	public function track_start_job( $job, $filename, string $trigger ) {
		if ( get_transient( 'backwpup_mcp_job_' . $job['jobid'] ) ) {
			return; // McpTracking handles this via backwpup_mcp_backup_triggered.
		}

		$this->tracking->track_start_job( $job, $trigger );
	}

	/**
	 * Track the end of a job.
	 *
	 * @param int    $job_id The ID of the job to delete.
	 * @param array  $job_details The details of the job storages.
	 * @param string $trigger Backup trigger.
	 *
	 * @return void
	 */
	public function track_end_job( $job_id, array $job_details, string $trigger ) {
		$transient_key = 'backwpup_mcp_job_' . $job_id;
		$context       = get_transient( $transient_key ) ? 'wp_plugin_mcp' : 'wp_plugin';

		if ( 'wp_plugin_mcp' === $context ) {
			delete_transient( $transient_key );
		}

		$this->tracking->track_end_job( $job_id, $job_details, $trigger, $context );
	}

	/**
	 * Track link clicked event.
	 *
	 * @param string $event The event name.
	 * @param array  $properties Additional event properties.
	 *
	 * @return void
	 */
	public function track_link_clicked( string $event, array $properties = [] ): void {
		$this->tracking->track_link_clicked( $event, $properties );
	}

	/**
	 * Track support tool button displayed event.
	 *
	 * @return void
	 */
	public function track_support_tool_button_displayed(): void {
		$this->tracking->track_support_tool_button_displayed();
	}

	/**
	 * Track support tool button clicked event.
	 *
	 * @return void
	 */
	public function track_support_tool_button_clicked(): void {
		$this->tracking->track_support_tool_button_clicked();
	}

	/**
	 * Track Expired banner shown event.
	 *
	 * @param string $license_state The license state when the banner is shown.
	 *
	 * @return void
	 */
	public function track_expired_banner_shown( $license_state ): void {
		$this->tracking->track_expired_banner_shown( $license_state );
	}

	/**
	 * Track log opened event.
	 *
	 * @param string $error_message The error message associated with the log, if any.
	 * @param int    $backup_id The ID of the backup.
	 * @param int    $job_id The ID of the job.
	 * @param bool   $job_completed Whether the job was completed or failed.
	 *
	 * @return void
	 */
	public function track_log_opened( $error_message, $backup_id, $job_id, $job_completed ): void {
		$this->tracking->track_log_opened( $error_message, $backup_id, $job_id, $job_completed );
	}

	/**
	 * Track dashboard viewed event.
	 *
	 * @return void
	 */
	public function track_dashboard_viewed(): void {
		$this->tracking->track_dashboard_viewed();
	}

	/**
	 * Track nudge impression event.
	 *
	 * @param string $location The screen or context where the nudge appeared.
	 *
	 * @return void
	 */
	public function track_nudge_impression( string $location ): void {
		$this->nudge_tracking->track_nudge_impression( $location );
	}

	/**
	 * Track nudge CTA click event.
	 *
	 * @param string $storage_slug The storage slug the CTA was clicked on.
	 *
	 * @return void
	 */
	public function track_nudge_click( string $storage_slug ): void {
		$this->nudge_tracking->track_nudge_click( $storage_slug );
	}

	/**
	 * Track locked option click event.
	 *
	 * @param string $storage_slug The locked storage slug the user attempted to select.
	 *
	 * @return void
	 */
	public function track_locked_option_click( string $storage_slug ): void {
		$this->nudge_tracking->track_locked_option_click( $storage_slug );
	}

	/**
	 * Handle AJAX nudge tracking requests from the front end.
	 *
	 * @return void
	 */
	public function ajax_track_nudge(): void {
		check_ajax_referer( 'backwpup_track_nudge', 'nonce' );

		$event   = isset( $_POST['event'] ) ? sanitize_key( wp_unslash( $_POST['event'] ) ) : '';
		$storage = isset( $_POST['storage'] ) ? sanitize_key( wp_unslash( $_POST['storage'] ) ) : '';

		if ( 'nudge_click' === $event ) {
			do_action( 'backwpup_track_nudge_click', $storage );
			wp_send_json_success();
		} elseif ( 'locked_option_click' === $event ) {
			do_action( 'backwpup_track_locked_option_click', $storage );
			wp_send_json_success();
		}

		wp_send_json_error(
			[ 'message' => 'Unsupported tracking event.' ],
			400
		);
	}
}
