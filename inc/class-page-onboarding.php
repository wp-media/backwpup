<?php

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
	 * Save post form.
	 * TODO Refactor this method using a Job class.
	 */
	public static function save_post_form() {
		$sanitized_data          = self::sanitize_post_data();
		$default_values          = BackWPup_Option::defaults_job();
		$job_types               = BackWPup::get_job_types();
		$default_id_job_files    = get_site_option( 'backwpup_backup_files_job_id', false );
		$default_id_job_database = get_site_option( 'backwpup_backup_database_job_id', false );
		$first_backup_job_id     = get_site_option( 'backwpup_first_backup_job_id', false );
		$first_job_frequency     = "job_{$default_id_job_files}_frequency";
		$second_job_frequency    = "job_{$default_id_job_database}_frequency";

		// The 2 base jobs.
		$job_frequency = [
			'files'    => [
				'job_id'    => $default_id_job_files,
				'frequency' => $sanitized_data[ $first_job_frequency ] ?? 'daily',
				'type'      => BackWPup_JobTypes::$type_job_files,
				'activ'     => isset( $sanitized_data[ $first_job_frequency ] ),
			],
			'database' => [
				'job_id'    => $default_id_job_database,
				'frequency' => $sanitized_data[ $second_job_frequency ] ?? 'daily',
				'type'      => BackWPup_JobTypes::$type_job_database,
				'activ'     => isset( $sanitized_data[ $second_job_frequency ] ),
			],
		];

		foreach ( $job_frequency as $key => $value ) {
			// General Part.
			update_site_option( "backwpup_backup_{$key}_job_id", $value['job_id'] );
			BackWPup_Option::update( $value['job_id'], 'name', BackWPup_JobTypes::${"name_job_{$key}"} );
			BackWPup_Option::update( $value['job_id'], 'jobid', (int) $value['job_id'] );
			BackWPup_Option::update( $value['job_id'], 'backuptype', $default_values['backuptype'] );
			BackWPup_Option::update( $value['job_id'], 'type', $value['type'] );
			BackWPup_Option::update( $value['job_id'], 'mailaddresslog', sanitize_email( $default_values['mailaddresslog'] ) );
			BackWPup_Option::update( $value['job_id'], 'mailaddresssenderlog', $default_values['mailaddresssenderlog'] );
			BackWPup_Option::update( $value['job_id'], 'mailerroronly', $default_values['mailerroronly'] );
			BackWPup_Option::update( $value['job_id'], 'archiveencryption', $default_values['archiveencryption'] );
			BackWPup_Option::update( $value['job_id'], 'archiveformat', $default_values['archiveformat'] );
			BackWPup_Option::update( $value['job_id'], 'archivename', BackWPup_Job::sanitize_file_name( BackWPup_Option::normalize_archive_name( $default_values['archivename'], $value['job_id'], false ) ) );

			// Cron part.
			BackWPup_Option::update( $value['job_id'], 'cron', BackWPup_Cron::get_basic_cron_expression( $value['frequency'] ) );

			if ( $value['activ'] ) {
				BackWPup_Option::update( $value['job_id'], 'activetype', $default_values['activetype'] );
				$cron_next = BackWPup_Cron::cron_next( BackWPup_Option::get( $value['job_id'], 'cron' ) );
				wp_clear_scheduled_hook( 'backwpup_cron', [ 'arg' => $value['job_id'] ] );
				wp_schedule_single_event( $cron_next, 'backwpup_cron', [ 'arg' => $value['job_id'] ] );
			} else {
				BackWPup_Option::update( $value['job_id'], 'activetype', '' );
			}

			// Save other form parts.
			if ( 'files' === $key && isset( $sanitized_data['backup_files'] ) && 'on' === $sanitized_data['backup_files'] ) {
				$job_types['FILE']->edit_form_post_save( $value['job_id'] );
				$job_types['FILE']->edit_form_post_save( $first_backup_job_id );
			}

			if ( 'database' === $key && isset( $sanitized_data['backup_database'] ) && 'on' === $sanitized_data['backup_database'] ) {
				$job_types['DBDUMP']->edit_form_post_save( $value['job_id'] );
				$job_types['DBDUMP']->edit_form_post_save( $first_backup_job_id );
			}

			BackWPup_Option::update( $value['job_id'], 'destinations', $sanitized_data['onboarding_storage'] );
		}
		$first_backup_type = [];
		if ( true === $job_frequency['files']['activ'] && false === $job_frequency['database']['activ'] ) {
			$first_backup_type = BackWPup_JobTypes::$type_job_files;
		} elseif ( false === $job_frequency['files']['activ'] && true === $job_frequency['database']['activ'] ) {
			$first_backup_type = BackWPup_JobTypes::$type_job_database;
		} else {
			$first_backup_type = BackWPup_JobTypes::$type_job_both;
		}
		BackWPup_Option::update( $first_backup_job_id, 'type', $first_backup_type );
		BackWPup_Option::update( $first_backup_job_id, 'destinations', $sanitized_data['onboarding_storage'] );

		// Onboarding OK.
		$onboarding_done = true;
		update_site_option( 'backwpup_onboarding', ! $onboarding_done );
		if ( defined( 'BWU_TESTING' ) ) {
			return;
		}

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
		$first_job_id        = get_site_option( 'backwpup_backup_files_job_id', false );
		$second_job_id       = get_site_option( 'backwpup_backup_database_job_id', false );
		$first_backup_job_id = get_site_option( 'backwpup_first_backup_job_id', false );

		if ( ! $first_job_id ) {
			$first_job_id = BackWPup_Option::create_default_jobs( BackWPup_JobTypes::$name_job_files, BackWPup_JobTypes::$type_job_files );
			update_site_option( 'backwpup_backup_files_job_id', $first_job_id );
		}
		if ( ! $second_job_id ) {
			$second_job_id = BackWPup_Option::create_default_jobs( BackWPup_JobTypes::$name_job_database, BackWPup_JobTypes::$type_job_database );
			update_site_option( 'backwpup_backup_database_job_id', $second_job_id );
		}
		if ( ! $first_backup_job_id ) {
			$first_backup_job_id = BackWPup_Option::create_default_jobs( 'First backup', BackWPup_JobTypes::$type_job_both );
			BackWPup_Option::update( $first_backup_job_id, 'tempjob', true );
			update_site_option( 'backwpup_first_backup_job_id', $first_backup_job_id );
		}

		include untrailingslashit( BackWPup::get_plugin_data( 'plugindir' ) ) . '/pages/onboarding.php';
	}
}
