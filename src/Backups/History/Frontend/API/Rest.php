<?php

namespace WPMedia\BackWPup\Backups\History\Frontend\API;

use WPMedia\BackWPup\API\Rest as RestInterface;
use WPMedia\BackWPup\Adapters\BackWPupAdapter;
use WPMedia\BackWPup\Adapters\OptionAdapter;
use WPMedia\BackWPup\Adapters\BackWPupHelpersAdapter;
use WPMedia\BackWPup\Backup\Database as BackupDatabase;
use WP_REST_Request;
use WP_REST_Response;

class Rest implements RestInterface {
	/**
	 * BackWPupAdapter instance.
	 *
	 * @var BackWPupAdapter
	 */
	private $backwpup_adapter;

	/**
	 * Option adapter instance.
	 *
	 * @var OptionAdapter
	 */
	private $option_adapter;

	/**
	 * BackWPupHelpersAdapter instance.
	 *
	 * @var BackWPupHelpersAdapter
	 */
	private $helpers_adapter;

	/**
	 * Backup database instance.
	 *
	 * @var BackupDatabase
	 */
	private $backup_database;

	/**
	 * Constructor for the Rest class.
	 *
	 * @param BackWPupAdapter        $backups_adapter   Adapter for handling backup operations.
	 * @param OptionAdapter          $option_adapter    Adapter for managing options.
	 * @param BackWPupHelpersAdapter $helpers_adapter   Adapter for helper functions.
	 * @param BackupDatabase         $backup_database   Backup database handler.
	 */
	public function __construct( BackWPupAdapter $backups_adapter, OptionAdapter $option_adapter, BackWPupHelpersAdapter $helpers_adapter, BackupDatabase $backup_database ) {
		$this->backwpup_adapter = $backups_adapter;
		$this->option_adapter   = $option_adapter;
		$this->helpers_adapter  = $helpers_adapter;
		$this->backup_database  = $backup_database;
	}

	/**
	 * Registers the REST API routes for the Backups History Frontend.
	 *
	 * This method is responsible for defining the routes that will be
	 * available in the REST API for interacting with the backups history
	 * frontend functionality.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			self::ROUTE_NAMESPACE,
			'/backups',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'get_backups_list' ],
				'permission_callback' => [ $this, 'has_permission' ],
				'args'                => [
					'page'   => [
						'default'           => 1,
						'sanitize_callback' => 'absint',
						'validate_callback' => function ( $param ) {
							return is_numeric( $param );
						},
					],
					'length' => [
						'default'           => 10,
						'sanitize_callback' => 'absint',
						'validate_callback' => function ( $param ) {
							return is_numeric( $param );
						},
					],
				],
			]
		);

		register_rest_route(
			self::ROUTE_NAMESPACE,
			'/pagination',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'get_pagination' ],
				'permission_callback' => [ $this, 'has_permission' ],
				'args'                => [
					'page'      => [
						'default'           => 1,
						'sanitize_callback' => 'absint',
						'validate_callback' => function ( $param ) {
							return is_numeric( $param );
						},
					],
					'max_pages' => [
						'default'           => 10,
						'sanitize_callback' => 'absint',
						'validate_callback' => function ( $param ) {
							return is_numeric( $param );
						},
					],
				],
			]
		);
	}
	/**
	 * Checks if the current user has the necessary permissions to access this functionality.
	 *
	 * @return bool True if the user has permission, false otherwise.
	 */
	public function has_permission(): bool {
		return current_user_can( 'backwpup' );
	}

	/**
	 * Get registered destinations
	 *
	 * @return array
	 */
	protected function get_registered_destinations(): array {
		return $this->backwpup_adapter->get_registered_destinations();
	}

	/**
	 * Retrieves a list of backups and returns them in a paginated format.
	 *
	 * @param WP_REST_Request $request The REST API request object containing parameters.
	 *
	 * @return WP_REST_Response The response object containing the backups HTML or an error message.
	 *
	 * The function performs the following steps:
	 * - Retrieves pagination parameters (`page` and `length`) from the request.
	 * - Fetches job IDs and iterates through each job to gather backup data.
	 * - Processes backup items for each destination associated with a job.
	 * - Ensures backups are unique by filtering duplicates based on `stored_on` and `filename`.
	 * - Sorts backups by their timestamp in descending order.
	 * - Paginates the backups based on the provided `page` and `length` parameters.
	 * - Generates HTML for the backups table rows and includes a hidden input for the total number of backups.
	 * - Returns a JSON response with the generated HTML or an error message if no backups are found.
	 *
	 * @throws \WP_Error If there is an issue with the request or processing backups.
	 */
	public function get_backups_list( WP_REST_Request $request ) {
		$params                  = $request->get_params();
		$page                    = empty( $params['page'] ) ? 1 : $params['page'];
		$length                  = empty( $params['length'] ) ? 10 : $params['length'];
		$jobs_ids                = $this->option_adapter->get_job_ids();
		$backups                 = [];
		$registered_destinations = $this->get_registered_destinations();

		foreach ( $jobs_ids as $a_job_id ) {
			$job = $this->option_adapter->get_job( $a_job_id );
			if ( ! $job ) {
				continue;
			}
			$dests    = $job['destinations'] ?? [];
			$job_data = [
				'id'       => $a_job_id,
				'name'     => $job['name'],
				'type'     => $job['activetype'],
				'data'     => [ 'Unknown' ],
				'logfile'  => $job['logfile'],
				'last_run' => $job['lastrun'] ?? null,
			];
			foreach ( $dests as $dest ) {
				if ( empty( $registered_destinations[ $dest ]['class'] ) ) {
					continue;
				}
				$dest_object = $this->backwpup_adapter->get_destination( $dest );
				if ( is_array( $dest_object ) ) {
					continue;
				}
				$items   = $dest_object->file_get_list( $a_job_id . '_' . $dest );
				$items   = $this->helpers_adapter->process_backup_items( $items, $job_data, $dest, $page );
				$backups = array_merge( $backups, $items );
			}
		}
		if ( 0 !== count( $jobs_ids ) ) {
			$default_location = $this->backwpup_adapter->get_destination( 'FOLDER' );
			if ( ! is_array( $default_location ) ) {
				$items   = $default_location->file_get_list();
				$items   = $this->helpers_adapter->process_backup_items( $items, $job_data, 'FOLDER', $page );
				$backups = array_merge( $backups, $items );
			}
		}
		$unique_backups = [];
		$seen           = [];
		foreach ( $backups as $item ) {
			$key = $item['stored_on'] . '|' . $item['filename'];
			if ( ! isset( $seen[ $key ] ) ) {
				$seen[ $key ]     = true;
				$unique_backups[] = $item;
			}
		}
		$backups = $unique_backups;
		$backups = $this->append_failed_backups( $backups );
		usort(
			$backups,
			function ( $a, $b ) {
				return $b['time'] <=> $a['time'];
			}
		);

		$backups         = wpm_apply_filters_typed( 'array', 'backwpup_backups_list', $backups );
		$nb_totalbackups = count( $backups );
		$total_pages     = ceil( $nb_totalbackups / $length );
		if ( $page > $total_pages ) {
			$page = $total_pages;
		}
		$start        = $page * $length - $length;
		$backups_list = array_slice( $backups, $start, $length );
		$html         = '';

		foreach ( $backups_list as $backup ) {
			if ( 'wpcron' === $backup['type'] ) {
				$backup['type'] = __( 'Scheduled', 'backwpup' );
			}
			$html .= $this->helpers_adapter->component( 'table-row-backups', [ 'backup' => $backup ], true );
		}
		$html .= $this->helpers_adapter->component(
			'form/hidden',
			[
				'name'  => 'nb_backups',
				'value' => $nb_totalbackups,
			],
			true
		);

		if ( empty( $html ) ) {
			return rest_ensure_response(
				[
					'success' => false,
					'data'    => '',
					'message' => __( 'No backups found.', 'backwpup' ),
					'page'    => 1,
				]
			);
		}

		return rest_ensure_response(
			[
				'success' => true,
				'data'    => $html,
				'page'    => $page,
			]
		);
	}

	/**
	 * Append failed backups from the backups table.
	 *
	 * @param array $backups Backups list.
	 *
	 * @return array
	 */
	private function append_failed_backups( array $backups ): array {
		$failed_rows = $this->backup_database->backups_list_by_status( 'failed' );

		if ( empty( $failed_rows ) ) {
			return $backups;
		}

		$existing = [];
		foreach ( $backups as $item ) {
			if ( empty( $item['stored_on'] ) || empty( $item['filename'] ) ) {
				continue;
			}
			$existing[ $item['stored_on'] . '|' . $item['filename'] ] = true;
		}

		foreach ( $failed_rows as $failed_row ) {
			if ( empty( $failed_row->filename ) || empty( $failed_row->destination ) ) {
				continue;
			}

			$key = $failed_row->destination . '|' . $failed_row->filename;
			if ( isset( $existing[ $key ] ) ) {
				continue;
			}
			$existing[ $key ] = true;

			$job_id   = (int) ( $failed_row->job_id ?? 0 );
			$job      = $job_id > 0 ? $this->option_adapter->get_job( $job_id ) : false;
			$job_data = $this->build_job_data( $job_id, $job );

			$backups[] = array_merge(
				$job_data,
				[
					'time'           => $this->normalize_backup_timestamp( $failed_row->submitted_at, $failed_row->modified ),
					'filename'       => (string) $failed_row->filename,
					'file'           => (string) $failed_row->filename,
					'stored_on'      => (string) $failed_row->destination,
					'backup_trigger' => (string) ( $failed_row->backup_trigger ?? '' ),
					'status'         => (string) ( $failed_row->status ?? '' ),
					'error_message'  => (string) ( $failed_row->error_message ?? '' ),
					'logfile'        => (string) ( $failed_row->logfile ?? '' ),
					'backup_id'      => (int) ( $failed_row->id ?? 0 ),
					'data'           => $this->build_backup_data( (string) $failed_row->filename, $job ),
				]
			);
		}

		return $backups;
	}

	/**
	 * Build job data for a backup entry.
	 *
	 * @param int        $job_id Job ID.
	 * @param array|bool $job Job data.
	 *
	 * @return array
	 */
	private function build_job_data( int $job_id, $job ): array {
		if ( ! is_array( $job ) ) {
			/* translators: %d: job id. */
			$job_name = $job_id > 0 ? sprintf( __( 'Job #%d', 'backwpup' ), $job_id ) : __( 'Unknown job', 'backwpup' );

			return [
				'id'       => $job_id,
				'name'     => $job_name,
				'type'     => '',
				'data'     => [ 'Unknown' ],
				'logfile'  => '',
				'last_run' => null,
			];
		}

		return [
			'id'       => $job_id,
			'name'     => $job['name'] ?? '',
			'type'     => $job['activetype'] ?? '',
			'data'     => [ 'Unknown' ],
			'logfile'  => $job['logfile'] ?? '',
			'last_run' => $job['lastrun'] ?? null,
		];
	}

	/**
	 * Build backup data types from filename or job configuration.
	 *
	 * @param string     $filename Backup filename.
	 * @param array|bool $job Job data.
	 *
	 * @return array
	 */
	private function build_backup_data( string $filename, $job ): array {
		$data = [];

		$filename_base = pathinfo( $filename, PATHINFO_FILENAME );
		if ( stripos( $filename, '.tar.gz' ) === strlen( $filename ) - 7 ) {
			$filename_base = substr( $filename, 0, -7 );
		} elseif ( stripos( $filename, '.tar.bz2' ) === strlen( $filename ) - 8 ) {
			$filename_base = substr( $filename, 0, -8 );
		}
		$filename_parts = explode( '_', $filename_base );

		if ( count( $filename_parts ) > 1 ) {
			$data = (array) explode( '-', end( $filename_parts ) );
		}

		if ( empty( $data ) && is_array( $job ) && ! empty( $job['type'] ) ) {
			$data = (array) $job['type'];
		}

		if ( empty( $data ) ) {
			$data = [ 'Unknown' ];
		}

		return $data;
	}

	/**
	 * Normalize backup timestamps.
	 *
	 * @param int $submitted_at Submitted timestamp.
	 * @param int $modified Modified timestamp.
	 *
	 * @return int
	 */
	private function normalize_backup_timestamp( int $submitted_at, int $modified ): int {
		if ( $submitted_at > 0 ) {
			return $submitted_at;
		}

		if ( $modified > 0 ) {
			return $modified;
		}

		return time();
	}

	/**
	 * Get the pagination of the history table.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function get_pagination( WP_REST_Request $request ): WP_REST_Response {
		$params    = $request->get_params();
		$page      = empty( $params['page'] ) ? 1 : $params['page'];
		$max_pages = empty( $params['max_pages'] ) ? 10 : $params['max_pages'];
		$html      = $this->helpers_adapter->component(
			'navigation/pagination',
			[
				'max_pages'    => $max_pages,
				'trigger'      => 'table-pagination',
				'class'        => 'max-md:hidden',
				'current_page' => $page,
			],
			true
		);
		if ( ! empty( $html ) ) {
			return rest_ensure_response(
				[
					'success' => true,
					'data'    => $html,
				]
				);
		} else {
			return rest_ensure_response(
				[
					'success' => false,
					'data'    => '',
					'message' => __( 'No pagination found.', 'backwpup' ),
				]
				);
		}
	}
}
