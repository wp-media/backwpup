<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Abilities\Jobs;

use WPMedia\BackWPup\Abilities\AbilitiesInterface;
use WPMedia\BackWPup\Backup\Database\Queries\Backup as BackupQuery;
use WPMedia\BackWPup\Adapters\BackWPupAdapter;
use WPMedia\BackWPup\Adapters\BackWPupHelpersAdapter;
use WPMedia\BackWPup\Adapters\JobAdapter;
use WPMedia\BackWPup\Adapters\OptionAdapter;

/**
 * GetBackupHistory Ability
 *
 * Returns recent backup history and whether a backup is currently running.
 */
class GetBackupHistory implements AbilitiesInterface {
	/**
	 * Ability ID
	 */
	private const ABILITY_ID = 'backwpup/get-backup-history';

	/**
	 * Tool name for MCP
	 */
	private const TOOL_NAME = 'backwpup_get_backup_history';

	/**
	 * Ability category
	 */
	private const CATEGORY = 'backwpup-jobs';

	/**
	 * Default limit for recent backups
	 */
	private const DEFAULT_LIMIT = 5;

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
	 * BackWPupHelpersAdapter instance
	 *
	 * @var BackWPupHelpersAdapter
	 */
	private BackWPupHelpersAdapter $helpers_adapter;

	/**
	 * JobAdapter instance
	 *
	 * @var JobAdapter
	 */
	private JobAdapter $job_adapter;

	/**
	 * BackupQuery instance
	 *
	 * @var BackupQuery|null
	 */
	private ?BackupQuery $backups_query;

	/**
	 * Constructor
	 *
	 * @param BackWPupAdapter        $backwpup_adapter BackWPupAdapter instance.
	 * @param OptionAdapter          $option_adapter   OptionAdapter instance.
	 * @param BackWPupHelpersAdapter $helpers_adapter  BackWPupHelpersAdapter instance.
	 * @param JobAdapter             $job_adapter      JobAdapter instance.
	 * @param BackupQuery|null       $backups_query    BackupQuery instance.
	 */
	public function __construct(
		BackWPupAdapter $backwpup_adapter,
		OptionAdapter $option_adapter,
		BackWPupHelpersAdapter $helpers_adapter,
		JobAdapter $job_adapter,
		?BackupQuery $backups_query = null
	) {
		$this->backwpup_adapter = $backwpup_adapter;
		$this->option_adapter   = $option_adapter;
		$this->helpers_adapter  = $helpers_adapter;
		$this->job_adapter      = $job_adapter;
		$this->backups_query    = $backups_query;
	}

	/**
	 * Register the ability
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
				'label'               => __( 'Get Backup History', 'backwpup' ),
				'category'            => self::CATEGORY,
				'description'         => __(
					'Returns recent backup history and whether a backup is currently running. Call this before triggering a new backup to avoid duplicates, and to inform the user of their latest backup status.',
					'backwpup'
				),
				'execute_callback'    => [ $this, 'execute' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'limit' => [
							'type'        => 'integer',
							'description' => __( 'Maximum number of recent backups to return', 'backwpup' ),
							'default'     => self::DEFAULT_LIMIT,
							'minimum'     => 1,
							'maximum'     => 50,
						],
					],
				],
				'output_schema'       => [
					'type'       => 'object',
					'properties' => [
						'is_running'     => [
							'type'        => 'boolean',
							'description' => __( 'Whether a backup job is currently active', 'backwpup' ),
						],
						'current_job'    => [
							'oneOf'       => [
								[ 'type' => 'null' ],
								[
									'type'       => 'object',
									'properties' => [
										'name'     => [
											'type'        => 'string',
											'description' => __( 'Name of the currently running job', 'backwpup' ),
										],
										'progress' => [
											'type'        => 'integer',
											'description' => __( 'Progress percentage (0-100)', 'backwpup' ),
											'minimum'     => 0,
											'maximum'     => 100,
										],
										'step'     => [
											'type'        => 'string',
											'description' => __( 'Current step being executed', 'backwpup' ),
										],
									],
								],
							],
							'description' => __( 'Information about the currently running job, if any', 'backwpup' ),
						],
						'recent_backups' => [
							'type'        => 'array',
							'description' => __( 'List of recent backups', 'backwpup' ),
							'items'       => [
								'type'       => 'object',
								'properties' => [
									'filename'    => [
										'type'        => 'string',
										'description' => __( 'Backup file name', 'backwpup' ),
									],
									'job_id'      => [
										'type'        => 'integer',
										'description' => __( 'ID of the job that created the backup', 'backwpup' ),
									],
									'job_name'    => [
										'type'        => 'string',
										'description' => __( 'Name of the job that created the backup', 'backwpup' ),
									],
									'destination' => [
										'type'        => 'string',
										'description' => __( 'Destination where the backup is stored', 'backwpup' ),
									],
									'size'        => [
										'type'        => 'integer',
										'description' => __( 'Backup file size in bytes', 'backwpup' ),
									],
									'time'        => [
										'type'        => 'integer',
										'description' => __( 'Unix timestamp of when the backup was created', 'backwpup' ),
									],
									'status'      => [
										'type'        => 'string',
										'description' => __( 'Backup status (e.g. created, completed, failed)', 'backwpup' ),
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
	 * Execute the ability
	 *
	 * @param array $args Input arguments.
	 *
	 * @return array|\WP_Error
	 */
	public function execute( array $args = [] ) {
		$start_time = microtime( true );

		$limit = isset( $args['limit'] ) ? (int) $args['limit'] : self::DEFAULT_LIMIT;
		$limit = max( 1, min( 50, $limit ) );

		// Get running job status.
		$running_data = $this->get_running_job_data();

		// Get recent backups.
		$recent_backups = $this->get_recent_backups( $limit );

		$success_result = [
			'is_running'     => $running_data['is_running'],
			'current_job'    => $running_data['current_job'],
			'recent_backups' => $recent_backups,
		];

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
	 * Get running job data
	 *
	 * @return array{is_running: bool, current_job: array|null}
	 */
	private function get_running_job_data(): array {
		$job_object = $this->job_adapter->get_working_data();

		if ( ! $job_object || ! is_object( $job_object ) ) {
			return [
				'is_running'  => false,
				'current_job' => null,
			];
		}

		return [
			'is_running'  => true,
			'current_job' => [
				'name'     => $job_object->job['name'] ?? __( 'Unknown Job', 'backwpup' ),
				'progress' => (int) ( $job_object->step_percent ?? 1 ),
				'step'     => (string) ( $job_object->step_working ?? 'CREATE' ),
			],
		];
	}

	/**
	 * Get recent backups
	 *
	 * Queries the database directly when available (complete, authoritative history).
	 * Falls back to reading destination file lists for legacy installs without DB records.
	 *
	 * @param int $limit Maximum number of backups to return.
	 *
	 * @return array
	 */
	private function get_recent_backups( int $limit ): array {
		if ( null !== $this->backups_query ) {
			return $this->get_recent_backups_from_db( $limit );
		}

		return $this->get_recent_backups_from_destinations( $limit );
	}

	/**
	 * Get recent backups from the database
	 *
	 * @param int $limit Maximum number of backups to return.
	 *
	 * @return array
	 */
	private function get_recent_backups_from_db( int $limit ): array {
		$registered_destinations = $this->backwpup_adapter->get_registered_destinations();
		$job_cache               = [];

		$rows = $this->backups_query->query(
			[
				'number'  => $limit,
				'orderby' => 'submitted_at',
				'order'   => 'DESC',
			]
		);

		if ( ! is_array( $rows ) ) {
			return [];
		}

		$backups = [];
		foreach ( $rows as $row ) {
			$job_id = (int) ( $row->job_id ?? 0 );

			if ( ! isset( $job_cache[ $job_id ] ) ) {
				$job                  = $job_id > 0 ? $this->option_adapter->get_job( $job_id ) : null;
				$job_cache[ $job_id ] = $job ? ( $job['name'] ?? __( 'Unknown Job', 'backwpup' ) ) : __( 'Unknown Job', 'backwpup' );
			}

			$dest_key = strtoupper( (string) ( $row->destination ?? '' ) );

			$backups[] = [
				'filename'    => (string) ( $row->filename ?? '' ),
				'job_id'      => $job_id,
				'job_name'    => $job_cache[ $job_id ],
				'destination' => $this->get_destination_label( $dest_key, $registered_destinations ),
				'size'        => 0,
				'time'        => (int) ( $row->submitted_at ?? 0 ),
				'status'      => (string) ( $row->status ?? '' ),
			];
		}

		return $backups;
	}

	/**
	 * Get recent backups by reading each destination's file list (legacy fallback)
	 *
	 * @param int $limit Maximum number of backups to return.
	 *
	 * @return array
	 */
	private function get_recent_backups_from_destinations( int $limit ): array {
		$jobs_ids                = $this->option_adapter->get_job_ids();
		$backups                 = [];
		$registered_destinations = $this->backwpup_adapter->get_registered_destinations();

		foreach ( $jobs_ids as $job_id ) {
			$job = $this->option_adapter->get_job( $job_id );
			if ( ! $job ) {
				continue;
			}

			$job_name     = $job['name'] ?? __( 'Unknown Job', 'backwpup' );
			$destinations = $job[' destinations'] ?? [];

			foreach ( $destinations as $dest ) {
				$normalized_dest = strtoupper( (string) $dest );

				if ( empty( $registered_destinations[ $normalized_dest ]['class'] ) ) {
					continue;
				}

				$dest_object = $this->backwpup_adapter->get_destination( $normalized_dest );
				if ( is_array( $dest_object ) ) {
					continue;
				}

				$items = $dest_object->file_get_list( $job_id . '_' . $normalized_dest );
				if ( ! is_array( $items ) ) {
					continue;
				}

				foreach ( $items as $item ) {
					if ( ! is_array( $item ) ) {
						continue;
					}

					$backups[] = [
						'filename'    => $item['file'] ?? '',
						'job_id'      => $job_id,
						'job_name'    => $job_name,
						'destination' => $this->get_destination_label( $normalized_dest, $registered_destinations ),
						'size'        => (int) ( $item['filesize'] ?? 0 ),
						'time'        => (int) ( $item['time'] ?? 0 ),
					];
				}
			}
		}

		// Also check default FOLDER destination.
		if ( count( $jobs_ids ) > 0 ) {
			$default_location = $this->backwpup_adapter->get_destination( 'FOLDER' );
			if ( ! is_array( $default_location ) ) {
				$items = $default_location->file_get_list();
				if ( is_array( $items ) ) {
					foreach ( $items as $item ) {
						if ( ! is_array( $item ) ) {
							continue;
						}

						$folder_job_id = $this->extract_job_id_from_filename( $item['file'] ?? '', $jobs_ids );
						$job_name      = $this->extract_job_name_from_filename( $item['file'] ?? '', $jobs_ids );

						$backups[] = [
							'filename'    => $item['file'] ?? '',
							'job_id'      => $folder_job_id,
							'job_name'    => $job_name,
							'destination' => __( 'Folder', 'backwpup' ),
							'size'        => (int) ( $item['filesize'] ?? 0 ),
							'time'        => (int) ( $item['time'] ?? 0 ),
							'status'      => '',
						];
					}
				}
			}
		}

		// Remove duplicates (same file in multiple destinations).
		$unique_backups = [];
		$seen           = [];
		foreach ( $backups as $backup ) {
			$key = $backup['filename'];
			if ( ! isset( $seen[ $key ] ) ) {
				$seen[ $key ]     = true;
				$unique_backups[] = $backup;
			}
		}

		usort(
			$unique_backups,
			function ( $a, $b ) {
				return $b['time'] <=> $a['time'];
			}
		);

		return array_slice( $unique_backups, 0, $limit );
	}

	/**
	 * Get destination label
	 *
	 * @param string $dest_key               Destination key.
	 * @param array  $registered_destinations Registered destinations.
	 *
	 * @return string
	 */
	private function get_destination_label( string $dest_key, array $registered_destinations ): string {
		$normalized_dest_key = strtoupper( $dest_key );

		if ( isset( $registered_destinations[ $normalized_dest_key ]['info']['name'] ) ) {
			return $registered_destinations[ $normalized_dest_key ]['info']['name'];
		}

		return $dest_key;
	}

	/**
	 * Extract job name from backup filename
	 *
	 * @param string $filename Backup filename.
	 * @param array  $job_ids  List of job IDs.
	 *
	 * @return string
	 */
	private function extract_job_name_from_filename( string $filename, array $job_ids ): string {
		// Try to match job ID from filename.
		foreach ( $job_ids as $job_id ) {
			if ( strpos( $filename, (string) $job_id ) !== false ) {
				$job = $this->option_adapter->get_job( $job_id );
				if ( $job && isset( $job['name'] ) ) {
					return $job['name'];
				}
			}
		}

		return __( 'Unknown Job', 'backwpup' );
	}

	/**
	 * Extract job ID from backup filename
	 *
	 * @param string $filename Backup filename.
	 * @param array  $job_ids  List of job IDs.
	 *
	 * @return int
	 */
	private function extract_job_id_from_filename( string $filename, array $job_ids ): int {
		foreach ( $job_ids as $job_id ) {
			if ( strpos( $filename, (string) $job_id ) !== false ) {
				return (int) $job_id;
			}
		}

		return 0;
	}
}
