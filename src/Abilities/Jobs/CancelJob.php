<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Abilities\Jobs;

use WPMedia\BackWPup\Abilities\AbilitiesInterface;
use WPMedia\BackWPup\Adapters\JobAdapter;

/**
 * CancelJob Ability
 *
 * Cancels/aborts a currently running backup job.
 */
class CancelJob implements AbilitiesInterface {
	/**
	 * Ability ID
	 */
	private const ABILITY_ID = 'backwpup/cancel-job';

	/**
	 * Tool name for MCP
	 */
	private const TOOL_NAME = 'backwpup_cancel_job';

	/**
	 * Ability category
	 */
	private const CATEGORY = 'backwpup-jobs';

	/**
	 * JobAdapter instance
	 *
	 * @var JobAdapter
	 */
	private JobAdapter $job_adapter;

	/**
	 * Constructor
	 *
	 * @param JobAdapter $job_adapter JobAdapter instance.
	 */
	public function __construct( JobAdapter $job_adapter ) {
		$this->job_adapter = $job_adapter;
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
				'label'               => __( 'Cancel Running Backup Job', 'backwpup' ),
				'category'            => self::CATEGORY,
				'description'         => __( 'Cancels a currently running backup job. Use this when a backup is stuck, taking too long, or needs to be stopped before starting a new one. Check backwpup_get_backup_history first to verify a job is running.', 'backwpup' ),
				'execute_callback'    => [ $this, 'execute' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [],
				],
				'output_schema'       => [
					'type'       => 'object',
					'properties' => [
						'success'  => [
							'type'        => 'boolean',
							'description' => __( 'Whether the job was successfully cancelled', 'backwpup' ),
						],
						'job_name' => [
							'type'        => 'string',
							'description' => __( 'Name of the job that was cancelled', 'backwpup' ),
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
		if ( current_user_can( 'backwpup_jobs_start' ) ) {
			return true;
		}

		do_action( 'backwpup_mcp_permission_denied', self::ABILITY_ID, self::TOOL_NAME, 'backwpup_jobs_start' );

		return false;
	}

	/**
	 * Execute the ability - cancel a running backup job
	 *
	 * @param array $args Input arguments (unused).
	 *
	 * @return array|\WP_Error
	 */
	public function execute( array $args = [] ) {
		$start_time = microtime( true );

		// Check if a job is currently running.
		$job_object = $this->job_adapter->get_working_data();

		if ( ! $job_object || ! is_object( $job_object ) ) {
			$error = new \WP_Error(
				'backwpup_no_job_running',
				__( 'No backup job is currently running. Nothing to cancel.', 'backwpup' ),
				[ 'status' => 404 ]
			);

			// Track failed execution.
			do_action(
				'backwpup_mcp_ability_executed',
				self::ABILITY_ID,
				self::TOOL_NAME,
				$error,
				$start_time,
				$args
			);

			return $error;
		}

		// Get job name before cancelling.
		$job_name = $job_object->job['name'] ?? __( 'Unknown Job', 'backwpup' );

		// Trigger the abort.
		$this->job_adapter->user_abort();

		$result = [
			'success'  => true,
			'job_name' => $job_name,
			// translators: %s: Job name.
			'message'  => sprintf( __( 'Job "%s" has been cancelled successfully.', 'backwpup' ), esc_attr( $job_name ) ),
		];

		// Track successful execution.
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
