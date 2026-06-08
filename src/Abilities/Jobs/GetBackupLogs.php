<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Abilities\Jobs;

use BackWPup_File;
use BackWPup_Job;
use WPMedia\BackWPup\Abilities\AbilitiesInterface;

/**
 * GetBackupLogs Ability
 *
 * Retrieves backup log content for troubleshooting and analysis.
 */
class GetBackupLogs implements AbilitiesInterface {
	/**
	 * Ability ID
	 */
	private const ABILITY_ID = 'backwpup/get-backup-logs';

	/**
	 * Tool name for MCP
	 */
	private const TOOL_NAME = 'backwpup_get_backup_logs';

	/**
	 * Ability category
	 */
	private const CATEGORY = 'backwpup-jobs';

	/**
	 * Default number of lines to return
	 */
	private const DEFAULT_LINES = 200;

	/**
	 * Maximum number of lines to return
	 */
	private const MAX_LINES = 1000;



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
				'label'               => __( 'Get Backup Logs', 'backwpup' ),
				'category'            => self::CATEGORY,
				'description'         => __( 'Retrieves backup log content for troubleshooting. Can get logs by backup ID, filename, or job ID. Use this when users ask to see backup logs or need to diagnose backup failures.', 'backwpup' ),
				'execute_callback'    => [ $this, 'execute' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'backup_id'   => [
							'type'        => 'integer',
							'description' => __( 'Backup ID from backup history (most specific)', 'backwpup' ),
						],
						'backup_file' => [
							'type'        => 'string',
							'description' => __( 'Backup filename (e.g., "backwpup_1_2024-04-23_10-30-00_ABC123.zip")', 'backwpup' ),
						],
						'job_id'      => [
							'type'        => 'integer',
							'description' => __( 'Job ID - returns latest log from this job', 'backwpup' ),
						],
						'lines'       => [
							'type'        => 'integer',
							'description' => __( 'Number of lines to return (default 200, max 1000)', 'backwpup' ),
							'default'     => self::DEFAULT_LINES,
							'maximum'     => self::MAX_LINES,
						],
					],
				],
				'output_schema'       => [
					'type'       => 'object',
					'properties' => [
						'success'     => [
							'type'        => 'boolean',
							'description' => __( 'Whether the log was retrieved successfully', 'backwpup' ),
						],
						'log_content' => [
							'type'        => 'string',
							'description' => __( 'The log content (plain text)', 'backwpup' ),
						],
						'metadata'    => [
							'type'        => 'object',
							'description' => __( 'Additional information about the backup', 'backwpup' ),
							'properties'  => [
								'backup_file'    => [
									'type'        => 'string',
									'description' => __( 'Backup filename', 'backwpup' ),
								],
								'job_name'       => [
									'type'        => 'string',
									'description' => __( 'Name of the backup job', 'backwpup' ),
								],
								'job_id'         => [
									'type'        => 'integer',
									'description' => __( 'Job ID', 'backwpup' ),
								],
								'timestamp'      => [
									'type'        => 'string',
									'description' => __( 'When the backup ran', 'backwpup' ),
								],
								'errors'         => [
									'type'        => 'integer',
									'description' => __( 'Number of errors in the log', 'backwpup' ),
								],
								'warnings'       => [
									'type'        => 'integer',
									'description' => __( 'Number of warnings in the log', 'backwpup' ),
								],
								'runtime'        => [
									'type'        => 'string',
									'description' => __( 'How long the backup took', 'backwpup' ),
								],
								'total_lines'    => [
									'type'        => 'integer',
									'description' => __( 'Total number of lines in the log', 'backwpup' ),
								],
								'returned_lines' => [
									'type'        => 'integer',
									'description' => __( 'Number of lines returned', 'backwpup' ),
								],
								'truncated'      => [
									'type'        => 'boolean',
									'description' => __( 'Whether the log was truncated', 'backwpup' ),
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
		if ( current_user_can( 'backwpup_logs' ) ) {
			return true;
		}

		do_action( 'backwpup_mcp_permission_denied', self::ABILITY_ID, self::TOOL_NAME, 'backwpup_logs' );

		return false;
	}

	/**
	 * Execute the ability - retrieve backup log content
	 *
	 * @param array $args Input arguments.
	 *
	 * @return array|\WP_Error
	 */
	public function execute( array $args = [] ) {
		$start_time = microtime( true );

		$backup_id   = isset( $args['backup_id'] ) ? absint( $args['backup_id'] ) : 0;
		$backup_file = isset( $args['backup_file'] ) ? sanitize_text_field( $args['backup_file'] ) : '';
		$job_id      = isset( $args['job_id'] ) ? absint( $args['job_id'] ) : 0;
		$lines       = isset( $args['lines'] ) ? absint( $args['lines'] ) : self::DEFAULT_LINES;

		// Enforce max lines.
		$lines = min( $lines, self::MAX_LINES );

		// Get log file path based on priority.
		$logfile_path = $this->resolve_logfile_path( $backup_id, $backup_file, $job_id );

		if ( ! $logfile_path ) {
			$result = new \WP_Error(
				'backwpup_log_not_found',
				__( 'No backup log found matching the criteria. Try using backwpup_get_backup_history to see available backups.', 'backwpup' ),
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

		// Read the log file.
		$log_data = $this->read_log_file( $logfile_path, $lines );

		if ( is_wp_error( $log_data ) ) {
			// Track execution.
			do_action(
				'backwpup_mcp_ability_executed',
				self::ABILITY_ID,
				self::TOOL_NAME,
				$log_data,
				$start_time,
				$args
			);

			return $log_data;
		}

		// Get log header metadata.
		$metadata = $this->get_log_metadata( $logfile_path );

		$success_result = [
			'success'     => true,
			'log_content' => implode( "\n", $log_data['lines'] ),
			'metadata'    => array_merge(
				$metadata,
				[
					'total_lines'    => $log_data['total_lines'],
					'returned_lines' => count( $log_data['lines'] ),
					'truncated'      => $log_data['truncated'],
				]
			),
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
	 * Resolve logfile path based on input parameters
	 *
	 * @param int    $backup_id   Backup ID.
	 * @param string $backup_file Backup filename.
	 * @param int    $job_id      Job ID.
	 *
	 * @return string|null
	 */
	private function resolve_logfile_path( int $backup_id, string $backup_file, int $job_id ): ?string {
		// Priority 1: Get by backup ID.
		if ( $backup_id > 0 ) {
			// Get database from container at runtime.
			$container = wpm_apply_filters_typed( 'object', 'backwpup_container', null );
			if ( $container && $container->has( 'backwpup_database' ) ) {
				$database   = $container->get( 'backwpup_database' );
				$backup_row = $database ? $database->get_backup_row_by_id( $backup_id ) : null;
				if ( $backup_row && ! empty( $backup_row->logfile ) ) {
					return $backup_row->logfile;
				}
			}
		}

		// Priority 2: Get by backup filename.
		if ( ! empty( $backup_file ) ) {
			return $this->find_logfile_by_backup_filename( $backup_file );
		}

		// Priority 3: Get latest log from specific job.
		if ( $job_id > 0 ) {
			return $this->find_latest_logfile_by_job_id( $job_id );
		}

		// Priority 4: Get the latest log overall.
		return $this->find_latest_logfile();
	}

	/**
	 * Find logfile by backup filename
	 *
	 * @param string $backup_file Backup filename.
	 *
	 * @return string|null
	 */
	private function find_logfile_by_backup_filename( string $backup_file ): ?string {
		// Priority 1: Query the database for this backup filename.
		// The database has the authoritative logfile path.
		$container = wpm_apply_filters_typed( 'object', 'backwpup_container', null );
		if ( $container && $container->has( 'backwpup_database' ) && $container->has( 'backups_query' ) ) {
			$database = $container->get( 'backwpup_database' );
			if ( $database ) {
				// Query by filename only (we don't know the destination_id).
				// Use the backups_query directly since get_backup_row() requires both destination and filename.
				$backups_query = $container->get( 'backups_query' );
				if ( $backups_query ) {
					$items = $backups_query->query(
						[
							'filename' => basename( $backup_file ),
							'number'   => 1,
							'orderby'  => 'created_at',
							'order'    => 'DESC',
						]
					);

					if ( ! empty( $items ) && ! empty( $items[0]->logfile ) ) {
						return $items[0]->logfile;
					}
				}
			}
		}

		// Priority 2: Fallback to filesystem search.
		// Extract the base pattern from the backup file.
		// Backup: backwpup_1_2024-04-23_10-30-00_ABC123.zip
		// OR:     2026-04-23_01-21-18_ETLBOUKW03_DBDUMP-FILE-WPPLUGIN.tar
		// Log:    backwpup_log_1_2024-04-23_10-30-00_ABC123.html(.gz)
		// OR:     backwpup_log_2026-04-23_01-21-18_ETLBOUKW03.html(.gz).
		$basename = basename( $backup_file, '.zip' );
		$basename = basename( $basename, '.tar.gz' );
		$basename = basename( $basename, '.tar' );

		$log_folder = $this->get_log_folder();
		if ( ! $log_folder ) {
			return null;
		}

		// Try to find matching log by searching for files with similar timestamp pattern.
		// Extract date-time pattern from basename (e.g., "2026-04-23_01-21-18").
		if ( preg_match( '/(\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2})/', $basename, $matches ) ) {
			$datetime_pattern = $matches[1];
			$pattern          = sprintf( '%s/backwpup_log*%s*.html*', $log_folder, $datetime_pattern );
			$matching_files   = glob( $pattern );

			if ( ! empty( $matching_files ) ) {
				// Return the first match.
				return $matching_files[0];
			}
		}

		// Fallback: Try standard variations.
		$log_basename = 'backwpup_log_' . ( 0 === strpos( $basename, 'backwpup_' ) ? substr( $basename, 9 ) : $basename );
		$candidates   = [
			$log_folder . '/' . $log_basename . '.html',
			$log_folder . '/' . $log_basename . '.html.gz',
			$log_folder . '/' . $basename . '.html',
			$log_folder . '/' . $basename . '.html.gz',
		];

		foreach ( $candidates as $path ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_readable
			if ( is_readable( $path ) ) {
				return $path;
			}
		}

		return null;
	}

	/**
	 * Find latest logfile by job ID
	 *
	 * Queries the database first (reliable for all filename formats), then falls
	 * back to a filesystem glob for legacy log file naming (backwpup_log_{job_id}_*).
	 *
	 * @param int $job_id Job ID.
	 *
	 * @return string|null
	 */
	private function find_latest_logfile_by_job_id( int $job_id ): ?string {
		// Priority 1: Query the database for the most recent backup from this job.
		$container = wpm_apply_filters_typed( 'object', 'backwpup_container', null );
		if ( $container && $container->has( 'backups_query' ) ) {
			$backups_query = $container->get( 'backups_query' );
			if ( $backups_query ) {
				$items = $backups_query->query(
					[
						'job_id'  => $job_id,
						'number'  => 1,
						'orderby' => 'submitted_at',
						'order'   => 'DESC',
					]
				);

				if ( ! empty( $items ) && ! empty( $items[0]->logfile ) ) {
					return $items[0]->logfile;
				}
			}
		}

		// Priority 2: Fallback filesystem glob for legacy log file naming (backwpup_log_{job_id}_*).
		$log_folder = $this->get_log_folder();
		if ( ! $log_folder ) {
			return null;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_readable
		if ( ! is_readable( $log_folder ) ) {
			return null;
		}

		$pattern  = sprintf( 'backwpup_log_%d_*.html*', $job_id );
		$logfiles = glob( $log_folder . '/' . $pattern );

		if ( empty( $logfiles ) ) {
			return null;
		}

		// Sort by modification time, newest first.
		usort(
			$logfiles,
			function ( $a, $b ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_filemtime
				return filemtime( $b ) - filemtime( $a );
			}
		);

		return $logfiles[0];
	}

	/**
	 * Find latest logfile overall
	 *
	 * @return string|null
	 */
	private function find_latest_logfile(): ?string {
		$log_folder = $this->get_log_folder();
		if ( ! $log_folder ) {
			return null;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_readable
		if ( ! is_readable( $log_folder ) ) {
			return null;
		}

		$logfiles = glob( $log_folder . '/backwpup_log_*.html*' );

		if ( empty( $logfiles ) ) {
			return null;
		}

		// Sort by modification time, newest first.
		usort(
			$logfiles,
			function ( $a, $b ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_filemtime
				return filemtime( $b ) - filemtime( $a );
			}
		);

		return $logfiles[0];
	}

	/**
	 * Read log file content
	 *
	 * @param string $logfile_path Path to log file.
	 * @param int    $max_lines    Maximum lines to return.
	 *
	 * @return array|\WP_Error
	 */
	private function read_log_file( string $logfile_path, int $max_lines ) {
		$path = null;

		// Determine the actual file path (handle .gz compression).
		// Check for .gz extension first so compress.zlib:// is always applied to gzipped files,
		// even when the path already ends in .gz (the plain is_readable branch must not win).
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_readable
		if ( substr( $logfile_path, -3 ) === '.gz' && is_readable( $logfile_path ) ) {
			$path = 'compress.zlib://' . $logfile_path;
		} elseif ( is_readable( $logfile_path ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_readable
			$path = $logfile_path;
		} elseif ( substr( $logfile_path, -5 ) !== '.html' && is_readable( $logfile_path . '.html' ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_readable
			$path = $logfile_path . '.html';
		} elseif ( substr( $logfile_path, -8 ) !== '.html.gz' && is_readable( $logfile_path . '.html.gz' ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_readable
			// Use compress.zlib wrapper for .gz files.
			$path = 'compress.zlib://' . $logfile_path . '.html.gz';
		}

		if ( ! $path ) {
			return new \WP_Error(
				'backwpup_log_unreadable',
				__( 'Log file exists but cannot be read.', 'backwpup' ),
				[ 'status' => 500 ]
			);
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$raw = file_get_contents( $path );
		if ( false === $raw ) {
			return new \WP_Error(
				'backwpup_log_read_error',
				__( 'Failed to read log file content.', 'backwpup' ),
				[ 'status' => 500 ]
			);
		}

		// Strip HTML and decode entities.
		$raw = wp_strip_all_tags( $raw );
		$raw = html_entity_decode( $raw );
		$raw = str_replace( "\r\n", "\n", $raw );

		$all_lines = [];
		foreach ( explode( "\n", $raw ) as $line ) {
			$line = trim( $line );
			if ( '' !== $line ) {
				$all_lines[] = $line;
			}
		}

		$total_lines = count( $all_lines );
		$truncated   = false;

		// Return last N lines if exceeds limit.
		if ( $total_lines > $max_lines ) {
			$lines     = array_slice( $all_lines, -$max_lines );
			$truncated = true;
		} else {
			$lines = $all_lines;
		}

		return [
			'lines'       => $lines,
			'total_lines' => $total_lines,
			'truncated'   => $truncated,
		];
	}

	/**
	 * Get log metadata
	 *
	 * @param string $logfile_path Path to log file.
	 *
	 * @return array
	 */
	private function get_log_metadata( string $logfile_path ): array {
		// Read log header using BackWPup's built-in method.
		$logheader = BackWPup_Job::read_logheader( $logfile_path );

		if ( empty( $logheader ) ) {
			return [
				'backup_file' => basename( $logfile_path ),
				'job_name'    => __( 'Unknown', 'backwpup' ),
				'job_id'      => 0,
				'timestamp'   => '',
				'errors'      => 0,
				'warnings'    => 0,
				'runtime'     => '',
			];
		}

		return [
			'backup_file' => ! empty( $logheader['file'] ) ? $logheader['file'] : basename( $logfile_path ),
			'job_name'    => ! empty( $logheader['name'] ) ? $logheader['name'] : __( 'Unknown', 'backwpup' ),
			'job_id'      => ! empty( $logheader['jobid'] ) ? absint( $logheader['jobid'] ) : 0,
			'timestamp'   => ! empty( $logheader['logtime'] ) ? (string) $logheader['logtime'] : '',
			'errors'      => ! empty( $logheader['errors'] ) ? absint( $logheader['errors'] ) : 0,
			'warnings'    => ! empty( $logheader['warnings'] ) ? absint( $logheader['warnings'] ) : 0,
			'runtime'     => ! empty( $logheader['runtime'] ) ? (string) $logheader['runtime'] : '',
		];
	}

	/**
	 * Get log folder path
	 *
	 * @return string|null
	 */
	private function get_log_folder(): ?string {
		$log_folder = get_site_option( 'backwpup_cfg_logfolder' );
		if ( empty( $log_folder ) ) {
			return null;
		}

		$log_folder = BackWPup_File::get_absolute_path( $log_folder );
		return untrailingslashit( $log_folder );
	}
}
