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
	 * The type of job for both files and database backup.
	 *
	 * @var array
	 */
	public static $type_job_both = [ 'FILE', 'DBDUMP', 'WPPLUGIN' ];

	/**
	 * Save post form.
	 * TODO Refactor this method using a Job class.
	 */
	public static function save_post_form() {
		$save                    = true;
		$sanitized_data          = self::sanitize_post_data();
		$default_values          = BackWPup_Option::defaults_job();
		$job_types               = BackWPup::get_job_types();
		$job_frequency           = [];
		$default_id_job_files    = get_site_option( 'backwpup_backup_files_job_id', false );
		$default_id_job_both     = get_site_option( 'backwpup_backup_files_job_id', false );
		$default_id_job_database = $default_id_job_both + 1;
		// Basic frequency.
		if ( isset( $sanitized_data['files_frequency'] ) && isset( $sanitized_data['database_frequency'] ) ) {
			if ( $sanitized_data['files_frequency'] === $sanitized_data['database_frequency'] ) {
				$job_frequency['database'] = [
					'job_id'    => $default_id_job_database,
					'frequency' => $sanitized_data['files_frequency'],
					'type'      => BackWPup_JobTypes::$type_job_database,
					'activ'     => false,
				];
				$job_frequency['both']     = [
					'job_id'    => $default_id_job_both,
					'frequency' => $sanitized_data['files_frequency'],
					'type'      => BackWPup_JobTypes::$type_job_both,
					'activ'     => true,
				];
			} else {
				$job_frequency['files']    = [
					'job_id'    => $default_id_job_files,
					'frequency' => $sanitized_data['files_frequency'],
					'type'      => BackWPup_JobTypes::$type_job_files,
					'activ'     => true,
				];
				$job_frequency['database'] = [
					'job_id'    => $default_id_job_database,
					'frequency' => $sanitized_data['database_frequency'],
					'type'      => BackWPup_JobTypes::$type_job_database,
					'activ'     => true,
				];
			}
		} elseif ( isset( $sanitized_data['files_frequency'] ) ) {
			$job_frequency['files']    = [
				'job_id'    => $default_id_job_files,
				'frequency' => $sanitized_data['files_frequency'],
				'type'      => BackWPup_JobTypes::$type_job_files,
				'activ'     => true,
			];
			$job_frequency['database'] = [
				'job_id'    => $default_id_job_database,
				'frequency' => $sanitized_data['files_frequency'],
				'type'      => BackWPup_JobTypes::$type_job_database,
				'activ'     => false,
			];
		} elseif ( isset( $sanitized_data['database_frequency'] ) ) {
			$job_frequency['files']    = [
				'job_id'    => $default_id_job_files,
				'frequency' => $sanitized_data['database_frequency'],
				'type'      => BackWPup_JobTypes::$type_job_files,
				'activ'     => false,
			];
			$job_frequency['database'] = [
				'job_id'    => $default_id_job_database,
				'frequency' => $sanitized_data['database_frequency'],
				'type'      => BackWPup_JobTypes::$type_job_database,
				'activ'     => true,
			];
		}
		// /Basic frequency.
		// Advanced frequency.
		// TODO
		// /Advanced frequency.
		foreach ( $job_frequency as $key => $value ) {
			// General Part.
			if ( true === $save ) {
				switch ( $key ) {
					case 'both':
						update_site_option( 'backwpup_backup_files_job_id', $value['job_id'] );
						update_site_option( 'backwpup_backup_database_job_id', $value['job_id'] );
						BackWPup_Option::update( $value['job_id'], 'name', BackWPup_JobTypes::$name_job_both );
						break;
					case 'files':
						update_site_option( 'backwpup_backup_files_job_id', $value['job_id'] );
						BackWPup_Option::update( $value['job_id'], 'name', BackWPup_JobTypes::$name_job_files );
						break;
					case 'database':
						update_site_option( 'backwpup_backup_database_job_id', $value['job_id'] );
						BackWPup_Option::update( $value['job_id'], 'name', BackWPup_JobTypes::$name_job_database );
						break;
				}
				BackWPup_Option::update( $value['job_id'], 'jobid', (int) $value['job_id'] );
				BackWPup_Option::update( $value['job_id'], 'backuptype', $default_values['backuptype'] );
				BackWPup_Option::update( $value['job_id'], 'type', $value['type'] );

				$email_log    = sanitize_email( $default_values['mailaddresslog'] );
				$email_sender = $default_values['mailaddresssenderlog'];
				BackWPup_Option::update( $value['job_id'], 'mailaddresslog', $email_log );
				BackWPup_Option::update( $value['job_id'], 'mailaddresssenderlog', $email_sender );
				BackWPup_Option::update( $value['job_id'], 'mailerroronly', $default_values['mailerroronly'] );

				BackWPup_Option::update( $value['job_id'], 'archiveencryption',  $default_values['archiveencryption'] );
				BackWPup_Option::update( $value['job_id'], 'archiveformat', $default_values['archiveformat'] );
				BackWPup_Option::update( $value['job_id'], 'archivename', BackWPup_Job::sanitize_file_name( BackWPup_Option::normalize_archive_name( $default_values['archivename'], $value['job_id'], false ) ) );
			}
			// /General Part
			// Test if job type makes backup.
			$makes_file = false;
			foreach ( $job_types as $type_id => $job_type ) {
				if ( in_array( $type_id, $value['type'], true ) ) {
					if ( $job_type->creates_file() ) {
						$makes_file = true;
						break;
					}
				}
			}

			// Cron Part.
			if ( true === $save ) {
				BackWPup_Option::update( $value['job_id'], 'cron', BackWPup_Cron::get_basic_cron_expression( $value['frequency'] ) );
				// Decisions not options.
				if ( true === $value['activ'] ) {
					// By default we use wpcron.
					BackWPup_Option::update( $value['job_id'], 'activetype', $default_values['activetype'] );
					$cron_next = BackWPup_Cron::cron_next( BackWPup_Option::get( $value['job_id'], 'cron' ) );
					// remove old schedule.
					wp_clear_scheduled_hook( 'backwpup_cron', [ 'arg' => $value['job_id'] ] );
					// make new schedule.
					wp_schedule_single_event( $cron_next, 'backwpup_cron', [ 'arg' => $value['job_id'] ] );
				} else {
					// No schedule for inactiv jobs.
					BackWPup_Option::update( $value['job_id'], 'activetype', '' );
				}
			}
			// /Cron Part.
			// Files Part.
			if ( isset( $sanitized_data['backup_files'] ) && 'on' === $sanitized_data['backup_files'] ) {
				if ( true === $save ) {
					$job_types['FILE']->edit_form_post_save( $value['job_id'] );
				}
			}
			// /Files Part.
			// Databade Part.
			if ( isset( $sanitized_data['backup_database'] ) && 'on' === $sanitized_data['backup_database'] ) {
				if ( true === $save ) {
					$job_types['DBDUMP']->edit_form_post_save( $value['job_id'] );
				}
			}
			// /Database Part.
			// Destination and storage part.
			$destinations = BackWPup::get_registered_destinations();
			if ( true === $save ) {
				BackWPup_Option::update( $value['job_id'], 'destinations', $sanitized_data['onboarding_storage'] );
			}
			// /Destination and storage part.
		}
		// Onboarding OK.
		$onboarding_done = true;
		update_site_option( 'backwpup_onboarding', ! $onboarding_done );
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
		include untrailingslashit( BackWPup::get_plugin_data( 'plugindir' ) ) . '/pages/onboarding.php';
	}
}
