<?php

use WPMedia\BackWPup\Backups\Onboarding\Onboarding;
use WPMedia\BackWPup\Plugin\Plugin;

class BackWPup_Page_Onboarding {

	/**
	 * The type of job for files only backup.
	 *
	 * @var array
	 */
	public static $type_job_files = [ 'FILE', 'WPPLUGIN' ];

	/**
	 * The type of job for database only backup.
	 *
	 * @var array
	 */
	public static $type_job_database = [ 'DBDUMP' ];

	/**
	 * Get onboarding job information.
	 *
	 * @param string $job_id The onboarding job id.
	 * @param string $job_frequency The job frequency value, could be cron expression.
	 *
	 * @return string
	 */
	private function get_onboarding_job_cron( string $job_id, string $job_frequency ): string {
		$get_job_cron = BackWPup_Option::get( $job_id, 'cron' );
		if ( is_null( $get_job_cron ) ) {
			$get_job_cron = BackWPup_Cron::get_basic_cron_expression( $job_frequency );
		}

		return $get_job_cron;
	}

	/**
	 * Save post form.
	 * TODO Refactor this method using a Job class.
	 */
	public static function save_post_form() {
		$sanitized_data          = self::sanitize_post_data();
		$default_values          = BackWPup_Option::defaults_job();
		$job_types               = BackWPup::get_job_types();
		$default_id_job_files    = get_site_option( Plugin::FILES_JOB_ID, false );
		$default_id_job_database = get_site_option( Plugin::DATABASE_JOB_ID, false );
		$first_backup_job_id     = get_site_option( Plugin::FIRST_JOB_ID, false );
		$first_job_frequency     = "job_{$default_id_job_files}_frequency";
		$second_job_frequency    = "job_{$default_id_job_database}_frequency";
		$files_job_status        = isset( $sanitized_data[ $first_job_frequency ] );
		$database_job_status     = isset( $sanitized_data[ $second_job_frequency ] );
		$files_job_frequency     = $sanitized_data[ $first_job_frequency ] ?? 'daily';
		$database_job_frequency  = $sanitized_data[ $second_job_frequency ] ?? 'daily';

		$onboarding_class = ( new self() );
		$files_cron_value = $onboarding_class->get_onboarding_job_cron( $default_id_job_files, $files_job_frequency );
		$db_cron_value    = $onboarding_class->get_onboarding_job_cron( $default_id_job_database, $database_job_frequency );

		$first_backup_job_data = BackWPup_Option::get_job( $first_backup_job_id );
		if ( ! $first_backup_job_data ) {
			$first_backup_job_data = [];
		}

		// The 2 base jobs.
		$job_frequency = [
			'files'    => array_merge(
				$first_backup_job_data,
				[
					'job_id'    => $default_id_job_files,
					'frequency' => $files_job_frequency,
					'type'      => BackWPup_JobTypes::$type_job_files,
					'activ'     => $files_job_status,
					'cron'      => $files_cron_value,
					'tempjob'   => false,
				]
				),
			'database' => array_merge(
				$first_backup_job_data,
				[
					'job_id'    => $default_id_job_database,
					'frequency' => $database_job_frequency,
					'type'      => BackWPup_JobTypes::$type_job_database,
					'activ'     => $database_job_status,
					'cron'      => $db_cron_value,
					'tempjob'   => false,
				]
				),
		];

		$mixed_data_type = array_filter( array_column( $job_frequency, 'activ' ) );

		if ( count( $mixed_data_type ) === count( $job_frequency ) &&
			$files_cron_value === $db_cron_value
		) {
			$mixed_job_data = array_merge(
				$first_backup_job_data,
				[
					'job_id'    => $default_id_job_files,
					'frequency' => $files_job_frequency,
					'type'      => BackWPup_JobTypes::$type_job_both,
					'activ'     => true,
					'cron'      => $files_cron_value,
					'tempjob'   => false,
				]
				);

			$job_frequency['both'] = $mixed_job_data;
			unset( $job_frequency['files'] );
			unset( $job_frequency['database'] );
		}

		/**
		 * Save backwpup options based on selected type during onboarding.
		 *
		 * @param array $job_frequency The job frequency based on user selected option during onboarding.
		 * @param array $default_values Backup default values.
		 */
		do_action( 'backwpup_onboarding_save_option', $job_frequency, $default_values );
		foreach ( $job_frequency as $key => $value ) {
			// We clone the first backup job data to ensure we have the same values for all jobs.
			foreach ( $value as $sub_key => $sub_value ) {
				if ( 'job_id' === $sub_key || 'jobid' === $sub_key || 'archivename' === $sub_key || 'name' === $sub_key ) {
					// Skip job_id,archive name and name fields.
					continue;
				}
				BackWPup_Option::update( $value['job_id'], $sub_key, $sub_value );
			}

			// Cron part.
			if ( $value['cron'] ) {
				BackWPup_Option::update( $value['job_id'], 'cron', $value['cron'] );
			}

			// Update cron expression with default only if default activation values are still set.
			if ( '0 0 1 * *' === BackWPup_Option::get( $value['job_id'], 'cron' ) && 'monthly' === BackWPup_Option::get( $value['job_id'], 'frequency' ) ) {
				BackWPup_Option::update( $value['job_id'], 'cron', BackWPup_Cron::get_basic_cron_expression( $value['frequency'] ) );
			}

			// Schedule the job if activated.
			if ( $value['activ'] ) {
				BackWPup_Option::update( $value['job_id'], 'activetype', $default_values['activetype'] );
				$cron_next     = BackWPup_Cron::cron_next( BackWPup_Option::get( $value['job_id'], 'cron' ) );
				$job_id_as_int = (int) $value['job_id'];
				wp_clear_scheduled_hook( 'backwpup_cron', [ 'arg' => $job_id_as_int ] );
				wp_schedule_single_event( $cron_next, 'backwpup_cron', [ 'arg' => $job_id_as_int ] );
			} else {
				BackWPup_Option::update( $value['job_id'], 'activetype', '' );
			}

			/**
			 * Save onboarding storage options to backup jobs
			 *
			 * @param mixed $job_id Job Id
			 * @param array $storages Array of selected/configured storages
			 */
			do_action( 'backwpup_onboarding_storage', $value['job_id'], $sanitized_data['onboarding_storage'] );

		}

		$first_backup_type = [];
		if ( 2 === count( $mixed_data_type ) ) {
			$first_backup_type = BackWPup_JobTypes::$type_job_both;
		} elseif ( true === $files_job_status ) {
			$first_backup_type = BackWPup_JobTypes::$type_job_files;
		} elseif ( true === $database_job_status ) {
			$first_backup_type = BackWPup_JobTypes::$type_job_database;
		}

		BackWPup_Option::update( $first_backup_job_id, 'type', $first_backup_type );
		BackWPup_Option::update( $first_backup_job_id, 'destinations', $sanitized_data['onboarding_storage'] );

		// Onboarding OK.
		$onboarding_done = true;
		update_site_option( 'backwpup_onboarding', ! $onboarding_done );
		if ( defined( 'BWU_TESTING' ) ) {
			return;
		}

		// Delete placeholder value jobs.
		if ( ! $database_job_status ) {
			BackWPup_Option::delete_job( $default_id_job_database );
		}

		if ( ! $files_job_status ) {
			BackWPup_Option::delete_job( $default_id_job_files );
		}

		// Delete the site options used for onboarding.
		delete_site_option( Plugin::FILES_JOB_ID );
		delete_site_option( Plugin::DATABASE_JOB_ID );

		wp_safe_redirect( network_admin_url( 'admin.php?page=backwpupfirstbackup' ) );
		exit;
	}

	/**
	 * Sanitize post data.
	 *
	 * @return array
	 */
	private static function sanitize_post_data() {
		$sanitized_data = [];
		foreach ( $_POST as $key => $value ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			// Skip nonce fields.
			if ( in_array( $key, [ '_wpnonce', '_wp_http_referer', 'backwpupajaxnonce' ], true ) ) {
				continue;
			}
			if ( is_array( $value ) ) {
				$sanitized_data[ $key ] = array_map( 'sanitize_text_field', wp_unslash( $value ) );
			} else {
				$sanitized_data[ $key ] = sanitize_text_field( wp_unslash( $value ) );
			}
		}
		$sanitized_data['activetype'] = '';
		return $sanitized_data;
	}

	/**
	 * Display the page content.
	 */
	public static function page() {
		$first_job_id        = get_site_option( Plugin::FILES_JOB_ID, false );
		$second_job_id       = get_site_option( Plugin::DATABASE_JOB_ID, false );
		$first_backup_job_id = get_site_option( Plugin::FIRST_JOB_ID, false );
		/**
		 * TODO:: Redefine onboarding so we don't need to have default jobs id.
		 * The initial job id created are deleted after onboarding except first backup
		 * This ids serve as placeholder for value which are needed to complete onboarding process.
		 * After onboarding is completed, the first backup job id is used to create the first backup job.
		 * first_job_id will be used to store files settings and second_job_id will be used to store database settings.
		*/
		if ( ! $first_backup_job_id ) {
			$first_backup_job_id = BackWPup_Option::create_default_jobs( 'First backup', BackWPup_JobTypes::$type_job_both );
			BackWPup_Option::update( $first_backup_job_id, 'tempjob', true );
			update_site_option( Plugin::FIRST_JOB_ID, $first_backup_job_id );
		}
		if ( ! $first_job_id ) {
			$first_job_id = $first_backup_job_id + 1;
			update_site_option( Plugin::FILES_JOB_ID, $first_job_id );
		}
		if ( ! $second_job_id ) {
			$second_job_id = $first_job_id + 1;
			update_site_option( Plugin::DATABASE_JOB_ID, $second_job_id );
		}

		include untrailingslashit( BackWPup::get_plugin_data( 'plugindir' ) ) . '/pages/onboarding.php';
	}
}
