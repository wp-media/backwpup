<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Abilities\Jobs;

use WPMedia\BackWPup\Abilities\AbilitiesInterface;
use WPMedia\BackWPup\Adapters\BackWPupAdapter;
use WPMedia\BackWPup\Adapters\OptionAdapter;

/**
 * Ability to list all configured BackWPup jobs.
 *
 * Returns structured information about backup jobs including destinations,
 * schedules, and last run information. Used by MCP clients to discover
 * backup configuration before risky operations.
 */
class GetJobsList implements AbilitiesInterface {
	/**
	 * Ability ID
	 */
	private const ABILITY_ID = 'backwpup/list-jobs';

	/**
	 * Tool name for MCP
	 */
	private const TOOL_NAME = 'backwpup_list_jobs';

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
	 * Constructor
	 *
	 * @param BackWPupAdapter $backwpup_adapter BackWPupAdapter instance.
	 * @param OptionAdapter   $option_adapter   OptionAdapter instance.
	 */
	public function __construct(
		BackWPupAdapter $backwpup_adapter,
		OptionAdapter $option_adapter
	) {
		$this->backwpup_adapter = $backwpup_adapter;
		$this->option_adapter   = $option_adapter;
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
				'label'               => __( 'List Backup Jobs', 'backwpup' ),
				'category'            => self::CATEGORY,
				'description'         => __( 'Returns all configured BackWPup backup jobs with their destinations (local folder, Dropbox, S3, etc.). Call this tool before performing any risky action on the website (plugin updates, theme changes, core updates, database migrations) to offer the user a backup first.', 'backwpup' ),
				'execute_callback'    => [ $this, 'execute' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'output_schema'       => [
					'type'       => 'object',
					'properties' => [
						'jobs' => [
							'type'  => 'array',
							'items' => [
								'type'       => 'object',
								'properties' => [
									'id'           => [
										'type'        => 'integer',
										'description' => 'Job ID',
									],
									'name'         => [
										'type'        => 'string',
										'description' => 'Job name',
									],
									'destinations' => [
										'type'        => 'array',
										'description' => 'Human-readable destination labels',
										'items'       => [
											'type' => 'string',
										],
									],
									'last_run'     => [
										'type'        => [ 'integer', 'null' ],
										'description' => 'Unix timestamp of last run, or null if never run',
									],
									'schedule'     => [
										'type'        => [ 'string', 'null' ],
										'description' => 'Cron expression for scheduled jobs, or null for manual jobs',
									],
								],
							],
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
	 * Execute the ability - list all configured jobs
	 *
	 * @return array|\WP_Error
	 */
	public function execute() {
		$start_time = microtime( true );

		$job_ids                 = $this->option_adapter->get_job_ids();
		$registered_destinations = $this->backwpup_adapter->get_registered_destinations();
		$jobs                    = [];

		foreach ( $job_ids as $job_id ) {
			$job = $this->option_adapter->get_job( $job_id );

			if ( ! $job ) {
				continue;
			}

			// Skip temporary jobs.
			if ( ! empty( $job['tempjob'] ) && true === $job['tempjob'] ) {
				continue;
			}

			// Map destination keys to human-readable labels.
			$destination_labels = [];
			if ( ! empty( $job['destinations'] ) && is_array( $job['destinations'] ) ) {
				foreach ( $job['destinations'] as $dest_key ) {
					$normalized_dest_key = strtoupper( (string) $dest_key );

					if ( isset( $registered_destinations[ $normalized_dest_key ]['info']['name'] ) ) {
						$destination_labels[] = $registered_destinations[ $normalized_dest_key ]['info']['name'];
					} else {
						// Fallback to original key if label not found.
						$destination_labels[] = $dest_key;
					}
				}
			}

			$jobs[] = [
				'id'           => $job_id,
				'name'         => $job['name'] ?? '',
				'destinations' => $destination_labels,
				'last_run'     => ! empty( $job['lastrun'] ) ? (int) $job['lastrun'] : null,
				'schedule'     => ! empty( $job['cron'] ) && ! empty( $job['activetype'] ) && 'wpcron' === $job['activetype'] ? $job['cron'] : null,
			];
		}

		$success_result = [
			'jobs' => $jobs,
		];

		// Track successful execution.
		do_action(
			'backwpup_mcp_ability_executed',
			self::ABILITY_ID,
			self::TOOL_NAME,
			$success_result,
			$start_time,
			[]
		);

		return $success_result;
	}
}
