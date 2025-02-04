<?php

class BackWPup_Migrate {

	/**
	 * Migrates data or settings for the BackWPup Pro plugin.
	 *
	 * This method handles the migration process for the BackWPup Pro plugin.
	 * It is responsible for updating or transforming data and settings to
	 * ensure compatibility with newer versions of the plugin.
	 *
	 * @return void
	 */
	public static function migrate() {
		$old_version = get_site_option( 'backwpup_version', '0.0.0' );
		$new_version = BackWPup::get_plugin_data( 'Version' );

		if ( ! self::should_migrate() ) {
			// Migration aborted. The plugin is already up to date.
			return;
		}

		$jobs = get_option( 'backwpup_jobs', [] );
		if ( empty( $jobs ) ) {
			self::redirect_to_onboarding();
			return;
		}

		$cron_jobs    = array_filter( $jobs, fn( $job ) => isset( $job['cron'] ) );
		$jobs_by_type = self::organize_jobs_by_type( $cron_jobs );

		if ( self::has_multiple_jobs_of_any_type( $jobs_by_type ) ) {
			self::redirect_to_onboarding();
			return;
		}

		self::convert_jobs( $jobs_by_type );
	}

	/**
	 * Determines whether a migration should be performed.
	 *
	 * This method checks the necessary conditions to decide if a migration process
	 * should be initiated.
	 *
	 * @return bool True if migration should be performed, false otherwise.
	 */
	public static function should_migrate() {
		$old_version = get_site_option( 'backwpup_version', '0.0.0' );
		$new_version = BackWPup::get_plugin_data( 'Version' );

		$version_migrate = version_compare( $old_version, '5.0.0', '<' ) && version_compare( $new_version, '5.0.0', '>=' );

		$options_exists = get_site_option( 'backwpup_backup_files_job_id', false ) && get_site_option( 'backwpup_backup_database_job_id', false );

		return $version_migrate && ! $options_exists;
	}

	/**
	 * Redirects the user to the onboarding page.
	 *
	 * This function is used to redirect the user to the onboarding page
	 * when certain conditions are met. It is a static method and can be
	 * called without instantiating the class.
	 *
	 * @return void
	 */
	private static function redirect_to_onboarding() {
		update_site_option( 'backwpup_onboarding', true );
	}


	/**
	 * Organizes the given jobs by their type.
	 *
	 * This function takes an array of jobs and organizes them based on their type.
	 *
	 * @param array $jobs An array of jobs to be organized.
	 *
	 * @return array An array of jobs organized by type.
	 */
	private static function organize_jobs_by_type( array $jobs ): array {
		$job_types = [
			'files'    => [],
			'database' => [],
			'both'     => [],
		];

		$type_both_template = BackWPup_JobTypes::$type_job_both;
		sort( $type_both_template );

		foreach ( $jobs as $job ) {
			$type = $job['type'];
			sort( $type );

			if ( BackWPup_JobTypes::$type_job_files === $type || [ 'FILE' ] === $type ) {
				$job_types['files'][] = $job;
			} elseif ( BackWPup_JobTypes::$type_job_database === $type ) {
				$job_types['database'][] = $job;
			} elseif ( $type === $type_both_template ) {
				$job['type']         = BackWPup_JobTypes::$type_job_both;
				$job_types['both'][] = $job;
			}
		}

		return $job_types;
	}

	/**
	 * Checks if there are multiple jobs of any type.
	 *
	 * This function takes an array of jobs categorized by type and determines
	 * if there are multiple jobs of any type present in the array.
	 *
	 * @param array $jobs_by_type An associative array where the keys are job types and the values are arrays of jobs.
	 * @return bool Returns true if there are multiple jobs of any type, false otherwise.
	 */
	private static function has_multiple_jobs_of_any_type( array $jobs_by_type ): bool {
		foreach ( $jobs_by_type as $type => $jobs ) {
			if ( count( $jobs ) > 1 ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Converts jobs by type.
	 *
	 * This function takes an array of jobs categorized by type and performs
	 * necessary conversions on them.
	 *
	 * @param array $jobs_by_type An associative array where the keys are job types
	 *                            and the values are arrays of jobs of that type.
	 *
	 * @return void
	 */
	private static function convert_jobs( array $jobs_by_type ) {
		update_site_option( 'backwpup_onboarding', false );

		// Set default job IDs.
		$default_id_job_files    = get_site_option( 'backwpup_backup_files_job_id', 1 );
		$default_id_job_both     = $default_id_job_files; // Reuse the ID for "both".
		$default_id_job_database = (int) $default_id_job_both + 1;
		$created_job             = [
			'files'    => false,
			'database' => false,
			'both'     => false,
		];
		// Handle 'both' jobs.
		$both_enabled = false;
		if ( count( $jobs_by_type['both'] ) === 1 ) {
			$both_enabled        = true;
			$created_job['both'] = true;
		}

		// Handle file and database jobs.
		if ( count( $jobs_by_type['files'] ) === 1 ) {
			if ( $both_enabled ) {
				BackWPup_Option::delete_job( $jobs_by_type['files'][0]['jobid'] );
			} else {
				self::handle_single_job(
					$jobs_by_type['files'][0],
					$default_id_job_files,
					'files',
					BackWPup_JobTypes::$name_job_files
				);
				BackWPup_Option::update( $default_id_job_files, 'type', BackWPup_JobTypes::$type_job_files );
				$created_job['files'] = true;
			}
		}
		if ( $both_enabled ) {
			self::handle_both_job( $jobs_by_type['both'][0], $default_id_job_both );
			$created_job['both'] = true;
		}

		if ( count( $jobs_by_type['database'] ) === 1 ) {
			self::handle_single_job(
				$jobs_by_type['database'][0],
				$default_id_job_database,
				'database',
				BackWPup_JobTypes::$name_job_database
			);
			$created_job['database'] = true;
			update_site_option( 'backwpup_backup_database_job_id', $default_id_job_database );

			if ( BackWPup_Option::get( $default_id_job_files, 'cron' ) === BackWPup_Option::get( $default_id_job_database, 'cron' ) ) {
				// If both jobs share the same cron schedule, update file job to handle both backups.
				BackWPup_Option::update( $default_id_job_files, 'type', BackWPup_JobTypes::$type_job_both );
				update_site_option( 'backwpup_backup_database_job_id', $default_id_job_files ); // Update the database job ID.
				BackWPup_Job::rename_job( $default_id_job_files, BackWPup_JobTypes::$name_job_both ); // Rename the file job.
				BackWPup_Job::enable_job( $default_id_job_files ); // Enable the file job.
				BackWPup_Job::disable_job( $default_id_job_database ); // Disable the database job.
			}
			if ( $both_enabled ) {
				BackWPup_Job::disable_job( $default_id_job_database );
				update_site_option( 'backwpup_backup_database_job_id', $default_id_job_both );
			}
		} elseif ( count( $jobs_by_type['database'] ) === 0 && $both_enabled ) {
			$duplicated_id = BackWPup_Job::duplicate_job( $default_id_job_both );
			if ( $duplicated_id !== $default_id_job_database ) {
				BackWPup_Option::update_job_id( $duplicated_id, $default_id_job_database );
			}

			BackWPup_Job::rename_job( $default_id_job_database, BackWPup_JobTypes::$name_job_database );
			BackWPup_Option::update( $default_id_job_database, 'type', BackWPup_JobTypes::$type_job_database );
			BackWPup_Job::disable_job( $default_id_job_database );
		}

		if ( $created_job['database'] && ! $created_job['files'] && ! $created_job['both'] ) {
			// Only DB has been migrated, we should create a new file job.
			$duplicated_id = BackWPup_Job::duplicate_job( $default_id_job_database );
			BackWPup_Option::update_job_id( $duplicated_id, $default_id_job_files );
			BackWPup_Job::rename_job( $default_id_job_files, BackWPup_JobTypes::$name_job_files );
			BackWPup_Option::update( $default_id_job_files, 'type', BackWPup_JobTypes::$type_job_files );
			BackWPup_Job::disable_job( $default_id_job_files );
			BackWPup_Option::update_job_id( $default_id_job_database, $default_id_job_files + 1 );
			update_site_option( 'backwpup_backup_files_job_id', $default_id_job_files );

			$created_job['files'] = true;
		} elseif ( $created_job['files'] && ! $created_job['database'] && ! $created_job['both'] ) {
			// Only files has been migrated, we should create a new database job.
			$duplicated_id = BackWPup_Job::duplicate_job( $default_id_job_files );
			BackWPup_Option::update_job_id( $duplicated_id, $default_id_job_database );
			BackWPup_Job::rename_job( $default_id_job_database, BackWPup_JobTypes::$name_job_database );
			BackWPup_Option::update( $default_id_job_database, 'type', BackWPup_JobTypes::$type_job_database );
			BackWPup_Job::disable_job( $default_id_job_database );
			update_site_option( 'backwpup_backup_database_job_id', $default_id_job_database );
			$created['database'] = true;
		} elseif ( ! $created_job['files'] && ! $created_job['database'] && $created_job['both'] ) {
			$duplicated_id = BackWPup_Job::duplicate_job( $default_id_job_both );
			BackWPup_Option::update_job_id( $duplicated_id, $default_id_job_database );
			BackWPup_Job::rename_job( $default_id_job_database, BackWPup_JobTypes::$name_job_database );
			BackWPup_Option::update( $default_id_job_database, 'type', BackWPup_JobTypes::$type_job_database );
			BackWPup_Job::disable_job( $default_id_job_database );
			update_site_option( 'backwpup_backup_database_job_id', $default_id_job_both );
			$created_job['database'] = true;
		}
	}

	/**
	 * Handles the migration of both job types.
	 *
	 * @param array $job The job configuration array.
	 * @param int   $default_id_job_both The default ID for both job types.
	 *
	 * @return void
	 */
	private static function handle_both_job( array $job, int $default_id_job_both ) {
		if ( ! BackWPup_Option::update_job_id( $job['jobid'], $default_id_job_both ) && $job['jobid'] !== $default_id_job_both ) {
			return;
		}

		update_site_option( 'backwpup_backup_files_job_id', $default_id_job_both );
		update_site_option( 'backwpup_backup_database_job_id', $default_id_job_both );
		BackWPup_Option::update( $default_id_job_both, 'type', BackWPup_JobTypes::$type_job_both );
		BackWPup_Job::rename_job( $default_id_job_both, BackWPup_JobTypes::$name_job_both );
		BackWPup_Job::enable_job( $default_id_job_both );
		BackWPup_Job::schedule_job( $default_id_job_both );
	}

	/**
	 * Handles a single job migration.
	 *
	 * @param array  $job        The job configuration array.
	 * @param int    $default_id The default job ID.
	 * @param string $type       The type of the job.
	 * @param string $name       The name of the job.
	 *
	 * @return void
	 */
	private static function handle_single_job( array $job, int $default_id, string $type, string $name ) {
		BackWPup_Option::update_job_id( $job['jobid'], $default_id );
		BackWPup_Option::update( $default_id, 'cron', $job['cron'] );
		update_site_option( "backwpup_backup_{$type}_job_id", $default_id );

		BackWPup_Job::rename_job( $default_id, $name );
		BackWPup_Job::enable_job( $default_id );
		BackWPup_Job::schedule_job( $default_id );
	}
}
