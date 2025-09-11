<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Tracking;

use WPMedia\BackWPup\Adapters\OptionAdapter;
use WPMedia\Mixpanel\Optin;
use WPMedia\Mixpanel\TrackingPlugin as MixpanelTracking;

class Tracking {
	/**
	 * Optin instance.
	 *
	 * @var Optin
	 */
	private $optin;

	/**
	 * Mixpanel Tracking instance.
	 *
	 * @var MixpanelTracking
	 */
	private $mixpanel;

	/**
	 * Option Adapter instance.
	 *
	 * @var OptionAdapter
	 */
	private $option_adapter;

	/**
	 * Tracking constructor.
	 *
	 * @param Optin            $optin Optin instance.
	 * @param MixpanelTracking $mixpanel Mixpanel Tracking instance.
	 * @param OptionAdapter    $option_adapter Option Adapter instance.
	 */
	public function __construct( Optin $optin, MixpanelTracking $mixpanel, OptionAdapter $option_adapter ) {
		$this->optin          = $optin;
		$this->mixpanel       = $mixpanel;
		$this->option_adapter = $option_adapter;
	}

	/**
	 * Update the Mixpanel opt-in setting.
	 *
	 * @return void
	 */
	public function update_setting(): void {
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'backwpup_page' ) ) {
			return;
		}

		if ( get_site_option( 'backwpup_onboarding', false ) ) {
			return;
		}

		$value = isset( $_POST['mixpanel'] ) ? 1 : 0;

		if ( 0 === $value ) {
			if ( $this->optin->is_enabled() ) {
				$this->optin->disable();
			}

			return;
		}

		if ( 1 === $value ) {
			$this->optin->enable();
		}
	}

	/**
	 * Track an opt-in change event.
	 *
	 * @param bool $optin The new opt-in value.
	 *
	 * @return void
	 */
	public function track_optin_change( $optin ): void {
		$user = wp_get_current_user();

		$this->mixpanel->identify( $user->user_email );
		$this->mixpanel->track_optin( $optin );
	}

	/**
	 * Track the addition of a new job.
	 *
	 * @param array $job The job data.
	 *
	 * @return void
	 */
	public function track_add_job( $job ) {
		if ( ! $this->optin->is_enabled() ) {
			return;
		}

		$user = wp_get_current_user();

		$this->mixpanel->identify( $user->user_email );

		$this->mixpanel->track(
			'Scheduled Backup Job Created',
			$this->get_event_properties( $job, true )
		);
	}

	/**
	 * Track the deletion of a job.
	 *
	 * @param int $job_id The ID of the job to delete.
	 *
	 * @return void
	 */
	public function track_delete_job( $job_id ) {
		if ( ! $this->optin->is_enabled() ) {
			return;
		}

		$user = wp_get_current_user();

		$this->mixpanel->identify( $user->user_email );

		$job = $this->option_adapter->get_job( $job_id );

		$this->mixpanel->track(
			'Scheduled Backup Job Deleted',
			$this->get_event_properties( $job )
		);
	}

	/**
	 * Get the properties for the event.
	 *
	 * @param array $job The job data.
	 * @param bool  $exclude_scheduled_info Exclude some scheduled info.
	 *
	 * @return array
	 */
	private function get_event_properties( array $job, bool $exclude_scheduled_info = false ): array {
		if ( empty( $job['archiveformat'] ) ) {
			$job['archiveformat'] = get_site_option( 'backwpup_archiveformat', '.tar' );
		}

		$properties = [
			'context'                => 'wp_plugin',
			'job_id'                 => $job['jobid'],
			'storage'                => $job['destinations'],
			'format'                 => $job['archiveformat'],
			'manually_excluded_data' => $this->has_manual_exclude( $job ),
		];

		if ( ! $exclude_scheduled_info ) {
			$properties['frequency'] = $job['frequency'];
			$properties['type']      = $job['type'];
		}

		return $properties;
	}

	/**
	 * Check if the job has manual exclusions.
	 *
	 * @param array $job The job data.
	 *
	 * @return bool
	 */
	private function has_manual_exclude( array $job ): bool {
		$defaults  = $this->option_adapter->defaults_job();
		$job_types = $job['type'] ?? [];

		// Define exclusions by backup type.
		$database_exclusions = [
			'dbdumpexclude',
		];

		$file_exclusions = [
			'backuprootexcludedirs',
			'backupcontentexcludedirs',
			'backuppluginsexcludedirs',
			'backupthemesexcludedirs',
			'backupuploadsexcludedirs',
			'fileexclude',
			'backuproot',
			'backupuploads',
			'backupthemes',
			'backupplugins',
			'backupcontent',
			'backupspecialfiles',
		];

		$exclusions = [];

		// Only check database exclusions if DBDUMP is in job types.
		if ( in_array( 'DBDUMP', $job_types, true ) ) {
			$exclusions = array_merge( $exclusions, $database_exclusions );
		}

		// Only check file exclusions if FILE is in job types.
		if ( in_array( 'FILE', $job_types, true ) ) {
			$exclusions = array_merge( $exclusions, $file_exclusions );
		}

		foreach ( $exclusions as $exclusion ) {
			if ( is_array( $job[ $exclusion ] ) && is_array( $defaults[ $exclusion ] ) ) {
				if ( ! empty( array_diff( $job[ $exclusion ], $defaults[ $exclusion ] ) ) ) {
					return true;
				}
			} elseif ( $job[ $exclusion ] !== $defaults[ $exclusion ] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Handle the AJAX request for the opt-in notice.
	 *
	 * @return void
	 */
	public function notice_optin_callback(): void {
		check_ajax_referer( 'backwpup_analytics_optin' );

		if ( ! isset( $_POST['value'] ) ) {
			wp_send_json_error( __( 'Missing opt-in value', 'backwpup' ) );
		}

		$value = sanitize_text_field( wp_unslash( $_POST['value'] ) );

		if ( 'no' === $value ) {
			wp_send_json_success();
		}

		$this->optin->enable();

		wp_send_json_success();
	}
}
