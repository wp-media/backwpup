<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Abilities\Jobs;

use WPMedia\BackWPup\Abilities\AbilitiesInterface;
use WPMedia\BackWPup\Adapters\BackWPupAdapter;
use WPMedia\BackWPup\Adapters\FileAdapter;
use WPMedia\BackWPup\Adapters\JobAdapter;
use WPMedia\BackWPup\Adapters\OptionAdapter;
use WPMedia\BackWPup\Adapters\JobTypesAdapter;

/**
 * RunJob Ability
 *
 * Triggers an immediate backup job - either a specific configured job or the default "Backup Now" behavior.
 */
class RunJob implements AbilitiesInterface {
	/**
	 * Ability ID
	 */
	private const ABILITY_ID = 'backwpup/run-job';

	/**
	 * Tool name for MCP
	 */
	private const TOOL_NAME = 'backwpup_run_job';

	/**
	 * Ability category
	 */
	private const CATEGORY = 'backwpup-jobs';

	/**
	 * BackWPupAdapter instance
	 *
	 * @var BackWPupAdapter
	 */
	private BackWPupAdapter $backwpup_adapter;

	/**
	 * OptionAdapter instance
	 *
	 * @var OptionAdapter
	 */
	private OptionAdapter $option_adapter;

	/**
	 * JobAdapter instance
	 *
	 * @var JobAdapter
	 */
	private JobAdapter $job_adapter;

	/**
	 * FileAdapter instance
	 *
	 * @var FileAdapter
	 */
	private FileAdapter $file_adapter;

	/**
	 * JobTypesAdapter instance
	 *
	 * @var JobTypesAdapter
	 */
	private JobTypesAdapter $job_types_adapter;

	/**
	 * Constructor
	 *
	 * @param BackWPupAdapter $backwpup_adapter BackWPupAdapter instance.
	 * @param OptionAdapter   $option_adapter   OptionAdapter instance.
	 * @param JobAdapter      $job_adapter      JobAdapter instance.
	 * @param FileAdapter     $file_adapter     FileAdapter instance.
	 * @param JobTypesAdapter $job_types_adapter JobTypesAdapter instance.
	 */
	public function __construct(
		BackWPupAdapter $backwpup_adapter,
		OptionAdapter $option_adapter,
		JobAdapter $job_adapter,
		FileAdapter $file_adapter,
		JobTypesAdapter $job_types_adapter
	) {
		$this->backwpup_adapter  = $backwpup_adapter;
		$this->option_adapter    = $option_adapter;
		$this->job_adapter       = $job_adapter;
		$this->file_adapter      = $file_adapter;
		$this->job_types_adapter = $job_types_adapter;
	}

	/**
	 * Register the ability with WordPress Abilities API
	 *
	 * @return void
	 */
	public function register(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		wp_register_ability(
			self::ABILITY_ID,
			[
				'label'               => __( 'Run Backup Job', 'backwpup' ),
				'category'            => self::CATEGORY,
				'description'         => __( 'Triggers an immediate backup. Pass a job_id from backwpup_list_jobs to run a specific job (e.g., Dropbox, S3). Omit job_id to trigger a default backup (files + database to local folder). Check backwpup_get_backup_history first to avoid starting a duplicate if one is already running.', 'backwpup' ),
				'execute_callback'    => [ $this, 'execute' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'job_id' => [
							'type'        => 'integer',
							'description' => __( 'Job ID to run. Omit to trigger default "Backup Now" job.', 'backwpup' ),
						],
					],
				],
				'output_schema'       => [
					'type'       => 'object',
					'properties' => [
						'success'  => [
							'type'        => 'boolean',
							'description' => __( 'Whether the job was successfully triggered', 'backwpup' ),
						],
						'job_name' => [
							'type'        => 'string',
							'description' => __( 'Name of the job that was triggered', 'backwpup' ),
						],
						'message'  => [
							'type'        => 'string',
							'description' => __( 'Human-readable status message', 'backwpup' ),
						],
					],
				],
				'meta'                => [
					'show_in_rest' => true,
					'mcp'          => [
						'public' => true,
					],
				],
			]
		);
	}

	/**
	 * Check if the current user has permission to execute this ability
	 *
	 * @return bool
	 */
	public function check_permissions(): bool {
		if ( current_user_can( 'backwpup' ) ) {
			return true;
		}

		do_action( 'backwpup_mcp_permission_denied', self::ABILITY_ID, self::TOOL_NAME, 'backwpup' );

		return false;
	}

	/**
	 * Execute the ability - trigger a backup job
	 *
	 * @param array $args Input arguments containing optional job_id.
	 *
	 * @return array|\WP_Error
	 */
	public function execute( array $args = [] ) {
		$start_time = microtime( true );

		// Check if a job is already running.
		$job_object = $this->job_adapter->get_working_data();
		if ( $job_object && is_object( $job_object ) ) {
			$result = new \WP_Error(
				'backwpup_job_already_running',
				__( 'A backup job is already running. Please wait for it to complete before starting another.', 'backwpup' ),
				[ 'status' => 409 ]
			);

			// Track execution.
			do_action(
				'backwpup_mcp_ability_executed',
				self::ABILITY_ID,
				self::TOOL_NAME,
				$result,
				$start_time,
				$args
			);

			return $result;
		}

		// Determine job ID.
		$job_id = $args['job_id'] ?? null;

		if ( null === $job_id ) {
			// Use "Backup Now" job - get existing or create if needed (same logic as Rest.php).
			$job_id = $this->get_job_id_when_no_available_job();
		} else {
			// Validate the provided job ID exists.
			$job = $this->option_adapter->get_job( $job_id );
			if ( ! $job ) {
				$result = new \WP_Error(
					'backwpup_invalid_job_id',
					// translators: %d: Job ID.
					sprintf( __( 'Job ID %d does not exist.', 'backwpup' ), $job_id ),
					[ 'status' => 404 ]
				);

				// Track execution.
				do_action(
					'backwpup_mcp_ability_executed',
					self::ABILITY_ID,
					self::TOOL_NAME,
					$result,
					$start_time,
					$args
				);

				return $result;
			}
		}

		// Pre-flight checks: temp folder.
		$temp_folder_message = $this->file_adapter->check_folder(
			$this->backwpup_adapter->get_plugin_data( 'TEMP' ),
			true
		);
		if ( ! empty( $temp_folder_message ) ) {
			$result = new \WP_Error(
				'backwpup_temp_folder_error',
				$temp_folder_message,
				[ 'status' => 500 ]
			);

			// Track execution.
			do_action(
				'backwpup_mcp_ability_executed',
				self::ABILITY_ID,
				self::TOOL_NAME,
				$result,
				$start_time,
				$args
			);

			return $result;
		}

		// Pre-flight checks: log folder.
		$log_folder         = get_site_option( 'backwpup_cfg_logfolder' );
		$log_folder         = $this->file_adapter->get_absolute_path( $log_folder );
		$log_folder_message = $this->file_adapter->check_folder( $log_folder );
		if ( ! empty( $log_folder_message ) ) {
			$result = new \WP_Error(
				'backwpup_log_folder_error',
				$log_folder_message,
				[ 'status' => 500 ]
			);

			// Track execution.
			do_action(
				'backwpup_mcp_ability_executed',
				self::ABILITY_ID,
				self::TOOL_NAME,
				$result,
				$start_time,
				$args
			);

			return $result;
		}

		// Get job data.
		$job_data = $this->option_adapter->get_job( $job_id );
		if ( ! $job_data ) {
			$result = new \WP_Error(
				'backwpup_job_data_error',
				__( 'Unable to retrieve job data.', 'backwpup' ),
				[ 'status' => 500 ]
			);

			// Track execution.
			do_action(
				'backwpup_mcp_ability_executed',
				self::ABILITY_ID,
				self::TOOL_NAME,
				$result,
				$start_time,
				$args
			);

			return $result;
		}

		// Pre-flight checks: backup destinations.
		$job_types    = $this->backwpup_adapter->get_job_types();
		$creates_file = false;
		foreach ( $job_types as $id => $job_type_class ) {
			if ( in_array( $id, $job_data['type'], true ) && $job_type_class->creates_file() ) {
				$creates_file = true;
				break;
			}
		}

		if ( $creates_file ) {
			$destinations = 0;
			foreach ( $job_data['destinations'] as $dest_key ) {
				$dest = $this->backwpup_adapter->get_destination( $dest_key );
				if ( ! $dest || ! $dest->can_run( $job_data ) ) {
					continue;
				}
				++$destinations;
			}

			if ( ! $destinations ) {
				$result = new \WP_Error(
					'backwpup_no_destination',
					// translators: %s: Job name.
					sprintf( __( 'No backup destination available for "%s" or properly configured!', 'backwpup' ), esc_attr( $job_data['name'] ) ),
					[ 'status' => 500 ]
				);

				// Track execution.
				do_action(
					'backwpup_mcp_ability_executed',
					self::ABILITY_ID,
					self::TOOL_NAME,
					$result,
					$start_time,
					$args
				);

				return $result;
			}
		}

		// Mark this job as MCP-initiated so standard Tracking skips it and McpTracking handles it.
		set_transient( 'backwpup_mcp_job_' . $job_id, true, 2 * HOUR_IN_SECONDS );

		// Trigger the job.
		$result = $this->job_adapter->get_jobrun_url( 'runnow', $job_id );

		// Check if the job started successfully.
		sleep( 1 ); // Wait for the job to start.
		$start_file_name = trailingslashit( $this->backwpup_adapter->get_plugin_data( 'TEMP' ) ) . '.backwpup_job_started';
		if ( ! file_exists( $start_file_name ) ) {
			$result = new \WP_Error(
				'backwpup_start_failed',
				__( 'The backup process could not be started! Please try again or contact support.', 'backwpup' ),
				[ 'status' => 500 ]
			);

			// Track execution.
			do_action(
				'backwpup_mcp_ability_executed',
				self::ABILITY_ID,
				self::TOOL_NAME,
				$result,
				$start_time,
				$args
			);

			return $result;
		}

		// Clean up the start file.
		wp_delete_file( $start_file_name );

		$success_result = [
			'success'  => true,
			'job_name' => $job_data['name'] ?? __( 'Unknown Job', 'backwpup' ),
			// translators: %s: Job name.
			'message'  => sprintf( __( 'Job "%s" started successfully.', 'backwpup' ), esc_attr( $job_data['name'] ) ),
		];

		// Track MCP-initiated backup.
		do_action(
			'backwpup_mcp_backup_triggered',
			$job_id,
			$job_data['name'] ?? __( 'Unknown Job', 'backwpup' ),
			null === $job_id,
			$job_data['destinations'] ?? [],
			$job_data['type'] ?? []
		);

		// Track successful execution.
		do_action(
			'backwpup_mcp_ability_executed',
			self::ABILITY_ID,
			self::TOOL_NAME,
			$success_result,
			$start_time,
			$args
		);

		return $success_result;
	}

	/**
	 * Get job ID when no available job exists (Backup Now logic)
	 *
	 * This reuses the logic from Rest.php to create or reuse a temp job for "Backup Now".
	 *
	 * @return int
	 */
	private function get_job_id_when_no_available_job(): int {
		$job_id = (int) get_site_option( 'backwpup_backup_now_job_id', 0 );
		if ( $job_id < 1 ) {
			$job_id = $this->option_adapter->next_job_id();
			update_site_option( 'backwpup_backup_now_job_id', $job_id );
			$this->option_adapter->update( $job_id, 'type', $this->job_types_adapter->get_type_job_both() );
		}

		$this->backup_now_default_values( $job_id, true );

		return $job_id;
	}

	/**
	 * Generate backup default values for "Backup Now" job
	 *
	 * @param int  $next_job_id The next job id.
	 * @param bool $backup_now  Check if it's a temp job or back up now.
	 *
	 * @return void
	 */
	private function backup_now_default_values( int $next_job_id, bool $backup_now = false ): void {
		$backup_now_job_values = [
			'jobid'              => $next_job_id,
			'name'               => 'Backup Now',
			'destinations'       => [ 'FOLDER' ],
			'activetype'         => '',
			'backupsyncnodelete' => false,
			'tempjob'            => true,
		];

		if ( $backup_now ) {
			$backup_now_job_values['tempjob']    = false;
			$backup_now_job_values['backup_now'] = true;
		}

		$default_values                    = $this->option_adapter->defaults_job();
		$default_destination_folder_values = $this->backwpup_adapter->get_destination( 'FOLDER' )->option_defaults();

		$bwp_job_values = array_merge( $default_values, $default_destination_folder_values, $backup_now_job_values );

		foreach ( $bwp_job_values as $key => $value ) {
			$this->option_adapter->update( $next_job_id, $key, $value );
		}
	}
}
