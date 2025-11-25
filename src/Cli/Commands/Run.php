<?php

namespace WPMedia\BackWPup\Cli\Commands;

use WPMedia\BackWPup\Adapters\BackWPupAdapter;
use WPMedia\BackWPup\Adapters\JobAdapter;
use WPMedia\BackWPup\Adapters\JobTypesAdapter;
use WPMedia\BackWPup\Adapters\OptionAdapter;
use WPMedia\BackWPup\Plugin\Plugin;

class Run implements Command {

	/**
	 * The job adapter instance.
	 *
	 * @var JobAdapter
	 */
	private JobAdapter $job_adapter;

	/**
	 * The option adapter instance.
	 *
	 * @var OptionAdapter
	 */
	private OptionAdapter $option_adapter;

	/**
	 * The BackWPup adapter instance.
	 *
	 * @var BackWPupAdapter
	 */
	private BackWPupAdapter $backwpup_adapter;

	/**
	 * The job types adapter instance.
	 *
	 * @var JobTypesAdapter
	 */
	private JobTypesAdapter $job_types_adapter;


	/**
	 * Constructor method.
	 *
	 * @param JobAdapter      $job_adapter The job adapter instance.
	 * @param OptionAdapter   $option_adapter The option adapter instance.
	 * @param BackWPupAdapter $backwpup_adapter The BackWPup adapter instance.
	 * @param JobTypesAdapter $job_types_adapter The job types adapter instance.
	 *
	 * @return void
	 */
	public function __construct( JobAdapter $job_adapter, OptionAdapter $option_adapter, BackWPupAdapter $backwpup_adapter, JobTypesAdapter $job_types_adapter ) {
		$this->job_adapter       = $job_adapter;
		$this->option_adapter    = $option_adapter;
		$this->backwpup_adapter  = $backwpup_adapter;
		$this->job_types_adapter = $job_types_adapter;
	}

	/**
	 * Start a BackWPup job.
	 *
	 * ## OPTIONS
	 *
	 * [<job_id>]
	 * : The IDs of the jobs to start as a comma-separated list.
	 *
	 * [--job_id=<job_id>]
	 * : The IDs of the jobs to start as a comma-separated list. (Same as only <job_id>.)
	 *
	 * [--now]
	 * : Starts a backup now. (Not working with <job_id>.)
	 *
	 * [--background]
	 * : Starts a backup as a background process. (Works only with one <job_id>)
	 *
	 * ## EXAMPLES
	 *
	 *     # Start a job with ID 1 in background.
	 *     $ wp backwpup run 1 --background
	 *     Success: Job "File" runs now in background.
	 *
	 *     # Start jobs with ID 1, 2 and 3. Jobs will run one after the other.
	 *     $ wp backwpup run 1,2,3
	 *     [INFO] BackWPup 5.6.0; A project of WP Media
	 *     ...
	 *     Job done in 2 seconds.
	 *     Successes: Job runs successfully.
	 *
	 *     # Start a backup now job.
	 *     $ wp backwpup run --now
	 *     [INFO] BackWPup 5.6.0; A project of WP Media
	 *     ...
	 *     Job done in 10 seconds.
	 *     Successes: Job runs successfully.
	 *
	 * @alias start
	 *
	 * @param array $args Positional arguments passed to the method.
	 * @param array $assoc_args Associative arguments passed to the method.
	 *
	 * @return void
	 */
	public function __invoke( array $args, array $assoc_args ) {
		$job_ids = [];

		if ( file_exists( $this->backwpup_adapter->get_plugin_data( 'running_file' ) ) ) {
			\WP_CLI::error( __( 'A job is already running.', 'backwpup' ) );
			return;
		}

		if ( ! empty( $assoc_args['job_id'] ) ) {
			$job_ids = array_map( 'intval', explode( ',', $assoc_args['job_id'] ) );
		}

		if ( ! $job_ids && ! empty( $args[0] ) ) {
			$job_ids = array_map( 'intval', explode( ',', $args[0] ) );
		}

		if ( ! $job_ids && ! $assoc_args ) {
			\WP_CLI::error( __( 'No job ID(s) or pram specified!', 'backwpup' ) );
			return;
		}

		if ( isset( $assoc_args['background'] ) ) {
			if ( count( $job_ids ) > 1 ) {
				\WP_CLI::error( __( 'Background job run not works with more than one job_id!', 'backwpup' ) );
				return;
			}
			if ( ! empty( $assoc_args['now'] ) ) {
				$job_ids = [ $this->get_backup_now_job_id() ];
			}
			$job_id        = array_shift( $job_ids );
			$error_message = wpm_apply_filters_typed( 'string', 'backwpup_job_not_started_error_message', '', $job_id );
			if ( $error_message ) {
				\WP_CLI::error( $error_message );
				return;
			}
			$result = $this->job_adapter->get_jobrun_url( 'runnow', $job_id );
			$name   = $this->option_adapter->get( $job_id, 'name' );
			if ( is_wp_error( $result ) || wp_remote_retrieve_body( $result ) ) {
				// translators: %s: job name.
				\WP_CLI::error( sprintf( __( 'Error on stating background job "%s"!', 'backwpup' ), $name ) );
				return;
			}
			// translators: %s: job name.
			\WP_CLI::success( sprintf( __( 'Job "%s" runs now in background.', 'backwpup' ), $name ) );
			return;
		}

		$started  = false;
		$errors   = 0;
		$warnings = 0;
		$jobs     = $this->job_adapter->get_jobs();
		foreach ( $jobs as $job ) {
			if ( (int) get_site_option( Plugin::FIRST_JOB_ID ) === $job['jobid'] ) {
				continue;
			}
			if ( ! empty( $job['tempjob'] ) ) {
				continue;
			}
			if ( in_array( $job['jobid'], $job_ids, true ) ) {
				$this->job_adapter->start_cli( $job['jobid'] );
				$started    = true;
				$log_file   = $this->option_adapter->get( $job['jobid'], 'logfile' );
				$log_header = $this->job_adapter->read_log_header( $log_file );
				$errors    += (int) $log_header['errors'];
				$warnings  += (int) $log_header['warnings'];
			}
		}

		if ( ! empty( $assoc_args['now'] ) ) {
			$job_id = $this->get_backup_now_job_id();
			$this->job_adapter->start_cli( $job_id );
			// get the last log file.
			$started    = true;
			$log_file   = $this->option_adapter->get( $job_id, 'logfile' );
			$log_header = $this->job_adapter->read_log_header( $log_file );
			$errors    += (int) $log_header['errors'];
			$warnings  += (int) $log_header['warnings'];
		}

		if ( $warnings ) {
			// translators: %d: number of warnings.
			\WP_CLI::warning( sprintf( __( 'There are %d wearings due to job execution please chack the logs.', 'backwpup' ), $warnings ) );
			return;
		}

		if ( $errors ) {
			// translators: %d: number of errors.
			\WP_CLI::error( sprintf( __( 'There are %d errors due to job execution please chack the logs!', 'backwpup' ), $errors ) );
			return;
		}

		if ( ! $started ) {
			\WP_CLI::error( __( 'Job ID does not exist!', 'backwpup' ) );
			return;
		}

		\WP_CLI::success( __( 'Job runs successfully.', 'backwpup' ) );
	}

	/**
	 * Retrieves or generates the job ID for the "Backup Now" job.
	 *
	 * This method attempts to retrieve the existing "Backup Now" job ID
	 * from the site options. If none is found, a new job ID is generated,
	 * default job settings are applied, and the job ID is stored in the site options.
	 *
	 * @return int The ID of the "Backup Now" job.
	 */
	private function get_backup_now_job_id(): int {
		$job_id = get_site_option( 'backwpup_backup_now_job_id', 0 );
		if ( 0 !== $job_id ) {
			return $job_id;
		}

		$job_id = $this->option_adapter->next_job_id();

		$backup_now_job_values = [
			'jobid'              => $job_id,
			'name'               => 'Backup Now',
			'type'               => $this->job_types_adapter->get_type_job_both(),
			'destinations'       => [ 'FOLDER' ],
			'activetype'         => '',
			'backupsyncnodelete' => false,
			'tempjob'            => false,
			'backup_now'         => true,
		];

		$default_values                    = $this->option_adapter->defaults_job();
		$default_destination_folder_values = $this->backwpup_adapter->get_destination( 'FOLDER' )->option_defaults();
		$bwp_job_values                    = array_merge(
			$default_values,
			$default_destination_folder_values,
			$backup_now_job_values
		);

		foreach ( $bwp_job_values as $key => $value ) {
			$this->option_adapter->update( $job_id, $key, $value );
		}

		update_site_option( 'backwpup_backup_now_job_id', $job_id );

		return $job_id;
	}


	/**
	 * Retrieves the command name.
	 *
	 * @inheritDoc
	 */
	public function get_name(): string {
		return 'run';
	}

	/**
	 * Retrieves the arguments for the command.
	 *
	 * @inheritDoc
	 */
	public function get_args(): array {
		return [];
	}
}
