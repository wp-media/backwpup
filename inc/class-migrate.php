<?php
use WPMedia\BackWPup\Plugin\Plugin;

class BackWPup_Migrate {
	/**
	 * Main migration method to handle multiple version upgrades.
	 *
	 * @return void
	 */
	public static function migrate() {
		$old_version = get_site_option( 'backwpup_version', '0.0.0' );
		$new_version = BackWPup::get_plugin_data( 'Version' );

		// Refresh Welcome Notice for update > 5.1.0.
		self::maybe_refresh_welcome_notice( $old_version, $new_version, '5.1.0' );

		if ( self::should_migrate( $old_version, $new_version ) ) {
			$jobs = get_option( 'backwpup_jobs', [] );
			if ( empty( $jobs ) ) {
				self::redirect_to_onboarding();
				return;
			}
			// Filter out jobs with 'cron' set.
			$cron_jobs    = array_filter( $jobs, fn( $job ) => isset( $job['cron'] ) );
			$jobs_by_type = self::organize_jobs_by_type( $cron_jobs );
			if ( self::has_multiple_jobs_of_any_type( $jobs_by_type ) ) {
				self::redirect_to_onboarding( $jobs );
				return;
			} elseif ( ! empty( $jobs ) ) { // Jobs are existing but not set with cron.
				self::set_jobs_legacy( $jobs, true );
			}

			self::convert_jobs( $jobs_by_type );
		}

		self::migration_50_51( $old_version, $new_version );

		( new self() )->migrate_storage_token( $old_version, $new_version );
	}

	/**
	 * Migrate onedrive storage token
	 *
	 * @param string $old_version The previous version of the plugin.
	 * @param string $new_version The current version of the plugin.
	 *
	 *  @return void
	 */
	public function migrate_storage_token( string $old_version, string $new_version ): void {
		$jobs = get_site_option( 'backwpup_jobs', [] );
		// If job is corrupt or not properly formatted then bail early.
		if ( ! is_array( $jobs ) ) {
			return;
		}

		$first_job_id = get_site_option( Plugin::FIRST_JOB_ID, false );
		if ( version_compare( $old_version, '5.2.3', '<=' )
			&& version_compare( $new_version, '5.3.0', '>=' )
		) {
			$backwpup_onedrive_state = get_site_transient( 'backwpup_onedrive_state' );
			if ( ! $backwpup_onedrive_state ) {
				// If the first job doesn't exist, create it.
				if ( ! $first_job_id ) {
					$first_job_id = BackWPup_Option::create_default_jobs( 'First backup', BackWPup_JobTypes::$type_job_both );
					BackWPup_Option::update( $first_job_id, 'tempjob', true );
					update_site_option( Plugin::FIRST_JOB_ID, $first_job_id );
				}
				foreach ( $jobs as $job ) {
					if (
						array_key_exists( 'onedrive_client_state', $job )
						&& ! is_null( $job['onedrive_client_state'] )
						&& $job['jobid'] !== $first_job_id
					) {
						BackWPup_Option::update(
							$first_job_id,
							'onedrive_client_state',
							$job['onedrive_client_state']
						);
					}
				}
			}
		}
	}

	/**
	 * Determines if the welcome notice should be refreshed based on version changes.
	 *
	 * This method compares the current version of the plugin with the target version.
	 * If the current version is greater than or equal to the target version, it removes
	 * the site option for the welcome notice, effectively refreshing it.
	 *
	 * @param string $old_version The previous version of the plugin.
	 * @param string $new_version The current version of the plugin.
	 * @param string $target_version The version that triggers the welcome notice refresh.
	 */
	private static function maybe_refresh_welcome_notice( string $old_version, string $new_version, string $target_version ): void {
		if ( $new_version === $old_version || ! version_compare( $new_version, $target_version, '>=' ) ) {
			return;
		}

		delete_site_option( 'backwpup_dinotopt_informations_505_notice' );
	}

	/**
	 * Determine if a migration is needed.
	 *
	 * This method checks if the plugin needs to migrate from a version older than 5.0.0 to 5.0.0 or newer.
	 * It also checks if the necessary options for the migration exist.
	 *
	 * @param string $old_version The old version of the plugin.
	 * @param string $new_version The new version of the plugin.
	 *
	 * @return bool True if migration is needed, false otherwise.
	 */
	public static function should_migrate( $old_version, $new_version ) {
		$version_migrate = version_compare( $old_version, '5.0.0', '<' ) && version_compare( $new_version, '5.0.0', '>=' );
		$options_exists  = get_site_option( Plugin::FILES_JOB_ID, false ) && get_site_option( Plugin::DATABASE_JOB_ID, false );
		return $version_migrate && ! $options_exists;
	}

	/**
	 * Redirect to the onboarding page.
	 *
	 * This method sets the `backwpup_onboarding` option to true to redirect the user to the onboarding page.
	 *
	 * @param array $jobs List of existing jobs to add legacy param.
	 *
	 * @return void
	 */
	private static function redirect_to_onboarding( array $jobs = [] ) {
		update_site_option( 'backwpup_onboarding', true );
		if ( ! empty( $jobs ) ) {
			foreach ( $jobs as $job ) {
				if ( isset( $job['jobid'] ) ) {
					BackWPup_Option::update( $job['jobid'], 'legacy', true );
				}
			}
		}
	}

	/**
	 * Organize jobs by type.
	 *
	 * This method organizes the jobs by type: files, database, or both.
	 *
	 * @param array $jobs The jobs to organize.
	 *
	 * @return array The organized jobs.
	 */
	private static function organize_jobs_by_type( array $jobs ): array {
		$job_types          = [
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
	 * Check if there are multiple jobs of any type.
	 *
	 * This method checks if there are multiple jobs of any type.
	 *
	 * @param array $jobs_by_type The jobs organized by type.
	 *
	 * @return bool True if there are multiple jobs of any type, false otherwise.
	 */
	private static function has_multiple_jobs_of_any_type( array $jobs_by_type ): bool {
		foreach ( $jobs_by_type as $jobs ) {
			if ( count( $jobs ) > 1 ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Convert jobs to the new structure.
	 *
	 * This method converts the jobs to the new structure.
	 *
	 * @param array $jobs_by_type The jobs organized by type.
	 *
	 * @return void
	 */
	private static function convert_jobs( array $jobs_by_type ) {
		update_site_option( 'backwpup_onboarding', false );
		$default_id_job_files    = get_site_option( Plugin::FILES_JOB_ID, BackWPup_Option::next_job_id() );
		$default_id_job_database = (int) $default_id_job_files + 1;
		update_site_option( Plugin::FILES_JOB_ID, $default_id_job_files );
		update_site_option( Plugin::DATABASE_JOB_ID, $default_id_job_database );

		$created_job = [
			'files'    => false,
			'database' => false,
		];

		// ✅ Remove all 'both' jobs.
		foreach ( $jobs_by_type['both'] as $both_job ) {
			BackWPup_Option::update( $both_job['jobid'], 'legacy', true );
		}

		// ✅ Handle FILES job.
		if ( count( $jobs_by_type['files'] ) === 1 ) {
			self::handle_single_job(
				$jobs_by_type['files'][0],
				$default_id_job_files,
				'files',
				BackWPup_JobTypes::$name_job_files
			);
			BackWPup_Option::update( $default_id_job_files, 'type', BackWPup_JobTypes::$type_job_files );
			$created_job['files'] = true;
			BackWPup_Option::update( $default_id_job_files, 'legacy', false );
			update_site_option( Plugin::FILES_JOB_ID, $default_id_job_files );
		}

		// ✅ Handle DATABASE job.
		if ( count( $jobs_by_type['database'] ) === 1 ) {
			self::handle_single_job(
				$jobs_by_type['database'][0],
				$default_id_job_database,
				'database',
				BackWPup_JobTypes::$name_job_database
			);
			BackWPup_Option::update( $default_id_job_database, 'type', BackWPup_JobTypes::$type_job_database );
			$created_job['database'] = true;
			BackWPup_Option::update( $default_id_job_database, 'legacy', false );
			update_site_option( Plugin::DATABASE_JOB_ID, $default_id_job_database );
		}

		// ✅ Ensure both jobs exist by duplicating if needed.
		if ( $created_job['database'] && ! $created_job['files'] ) {
			$duplicated_id = BackWPup_Job::duplicate_job( $default_id_job_database );
			BackWPup_Option::update_job_id( $duplicated_id, $default_id_job_files );
			BackWPup_Job::rename_job( $default_id_job_files, BackWPup_JobTypes::$name_job_files );
			BackWPup_Option::update( $default_id_job_files, 'type', BackWPup_JobTypes::$type_job_files );
			BackWPup_Job::disable_job( $default_id_job_files );
			BackWPup_Option::update_job_id( $default_id_job_database, $default_id_job_files + 1 );
			update_site_option( Plugin::FILES_JOB_ID, $default_id_job_files );
			BackWPup_Option::update( $default_id_job_files, 'legacy', false );
			$created_job['files'] = true;
		}

		if ( $created_job['files'] && ! $created_job['database'] ) {
			$duplicated_id = BackWPup_Job::duplicate_job( $default_id_job_files );
			BackWPup_Option::update_job_id( $duplicated_id, $default_id_job_database );
			BackWPup_Job::rename_job( $default_id_job_database, BackWPup_JobTypes::$name_job_database );
			BackWPup_Option::update( $default_id_job_database, 'type', BackWPup_JobTypes::$type_job_database );
			BackWPup_Job::disable_job( $default_id_job_database );
			update_site_option( Plugin::DATABASE_JOB_ID, $default_id_job_database );
			BackWPup_Option::update( $default_id_job_database, 'legacy', false );
			$created_job['database'] = true;
		}
	}


	/**
	 * Handle single job.
	 *
	 * This method handles a single job.
	 *
	 * @param array  $job The job to handle.
	 * @param int    $default_id The default job ID.
	 * @param string $type The type of job.
	 * @param string $name The name of the job.
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

	/**
	 * Migration logic for upgrading from BackWPup 5.0.x to 5.1.0+
	 *
	 * This method handles the migration process for updating the plugin from version 5.0.x to 5.1.0.
	 * It updates job options and marks the migration as complete.
	 *
	 * @param string $old_version The old version of the plugin.
	 * @param string $new_version The new version of the plugin.
	 *
	 * @return void
	 */
	public static function migration_50_51( $old_version, $new_version ) {
		if ( version_compare( $old_version, '5.1.0', '<' ) && version_compare( $new_version, '5.1.0', '>=' ) ) {
			$default_50_file_job = get_site_option( Plugin::FILES_JOB_ID, 1 );
			$default_50_db_job   = $default_50_file_job + 1;
			if ( get_site_option( Plugin::DATABASE_JOB_ID, 2 ) === $default_50_file_job ) {
				update_site_option( Plugin::DATABASE_JOB_ID, $default_50_db_job );
			}

			$file_job = BackWPup_Option::get_job( $default_50_file_job );
			$db_job   = BackWPup_Option::get_job( $default_50_db_job );
			if ( false === $file_job && false === $db_job ) {
				$default_jobs = BackWPup_Option::get_default_jobs();
				$jobs         = BackWPup_Job::get_jobs();
				$jobs         = array_merge( $default_jobs, $jobs );
				update_site_option( 'backwpup_jobs', $jobs );
			}
			$job_ids = BackWPup_Option::get_job_ids();
			foreach ( $job_ids as $id ) {
				if ( (int) $id === (int) $default_50_file_job || (int) $id === (int) $default_50_db_job ) {
					BackWPup_Option::update( $id, 'legacy', false );
				} else {
					BackWPup_Option::update( $id, 'legacy', true );
				}
			}
		}
	}

	/**
	 * Set legacy jobs.
	 *
	 * This method sets the legacy flag for jobs.
	 *
	 * @param array $jobs The jobs to set legacy for.
	 * @param bool  $legacy The legacy flag.
	 *
	 * @return void
	 */
	private static function set_jobs_legacy( array $jobs, bool $legacy = false ) {
		foreach ( $jobs as $job ) {
			if ( isset( $job['jobid'] ) ) {
				BackWPup_Option::update( $job['jobid'], 'legacy', $legacy );
			}
		}
	}
}
