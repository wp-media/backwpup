<?php

namespace WPMedia\BackWPup\Backups\API;

use WPMedia\BackWPup\Adapters\BackWPupAdapter;
use WPMedia\BackWPup\Adapters\OptionAdapter;
use WPMedia\BackWPup\Adapters\JobAdapter;
use WPMedia\BackWPup\Adapters\FileAdapter;
use WPMedia\BackWPup\Adapters\JobTypesAdapter;
use WP_HTTP_Response;
use WP_REST_Request;
use WP_REST_Response;
use Exception;
use WPMedia\BackWPup\API\Rest as RestInterface;


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
	 * JobAdapter instance.
	 *
	 * @var JobAdapter
	 */
	private $job_adapter;

	/**
	 * FileAdapter instance.
	 *
	 * @var FileAdapter
	 */
	private $file_adapter;

	/**
	 * JobTypesAdapter instance.
	 *
	 * @var JobTypesAdapter
	 */
	private $job_types_adapter;

	/**
	 * Constructor.
	 *
	 * @param BackWPupAdapter $backups_adapter
	 * @param OptionAdapter   $option_adapter
	 * @param JobAdapter      $job_adapter
	 * @param FileAdapter     $file_adapter
	 * @param JobTypesAdapter $job_types_adapter
	 */
	public function __construct(
		BackWPupAdapter $backups_adapter,
		OptionAdapter $option_adapter,
		JobAdapter $job_adapter,
		FileAdapter $file_adapter,
		JobTypesAdapter $job_types_adapter
	) {
		$this->backwpup_adapter  = $backups_adapter;
		$this->option_adapter    = $option_adapter;
		$this->job_adapter       = $job_adapter;
		$this->file_adapter      = $file_adapter;
		$this->job_types_adapter = $job_types_adapter;
	}

	/**
	 * Checks if the current user has the necessary permissions to perform the action.
	 *
	 * @return bool True if the user has permission, false otherwise.
	 */
	public function has_permission(): bool {
		return current_user_can( 'backwpup' );
	}

	/**
	 * Registers the REST API routes for the Backups API.
	 *
	 * This method is responsible for defining the endpoints and their
	 * corresponding callbacks for the Backups API within the BackWPup Pro plugin.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			'backwpup/v1',
			'/startbackup',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'start_backup' ],
				'permission_callback' => [ $this, 'has_permission' ],
				'args'                => [
					'job_id'       => [
						'required'          => false,
						'validate_callback' => function ( $param ) {
							return is_numeric( $param ) && $param > 0;
						},
						'sanitize_callback' => 'absint',
					],
					'first_backup' => [
						'required'          => false,
						'validate_callback' => function ( $param ) {
							return filter_var( $param, FILTER_VALIDATE_BOOLEAN );
						},
						'sanitize_callback' => function ( $param ) {
							return filter_var( $param, FILTER_VALIDATE_BOOLEAN );
						},
					],
				],
			]
		);

		register_rest_route(
			'backwpup/v1',
			'/process_bulk_actions',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'process_bulk_actions' ],
				'permission_callback' => [ $this, 'has_permission' ],
				'args'                => [
					'action'  => [
						'required'          => true,
						'validate_callback' => function ( $param ) {
							return in_array( $param, [ 'delete' ], true );
						},
						'sanitize_callback' => 'sanitize_text_field',
					],
					'backups' => [
						'required'          => true,
						'validate_callback' => function ( $param ) {
							return is_array( $param ) && ! empty( $param );
						},
					],
				],
			]
		);
	}

	/**
	 * Starts the backup process.
	 *
	 * This function is triggered by a WP REST API request to initiate a backup.
	 * If a job ID is provided, it will start the backup for that job. Otherwise, it will start the backup for the file job.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error|WP_HTTP_Response The response object on success, or WP_Error on failure.
	 */
	public function start_backup( WP_REST_Request $request ) {
		$params = $request->get_params();

		if ( ! empty( $params['first_backup'] ) ) {
			if ( false === get_site_transient( 'backwpup_first_backup' ) ) {
				set_site_transient( 'backwpup_first_backup', true, HOUR_IN_SECONDS );
			} else {
				return new WP_REST_Response(
					[
						'status' => 301,
						'url'    => network_admin_url( 'admin.php?page=backwpup' ),
					],
					200
				);
			}
		}

		$create_backup_now_job = true;
		$jobs                  = $this->job_adapter->get_jobs();
		foreach ( $jobs as $job ) {
			if (
				isset( $job['backup_now'] ) && true === $job['backup_now']
				&& ! empty( $job['activetype'] )
			) {
				$create_backup_now_job = false;
			}
		}

		$jobid = $params['job_id'] ?? false;
		if ( $create_backup_now_job && false === $jobid ) {
			$jobid = $this->get_job_id_when_no_available_job();
		}

		// check temp folder.
		$temp_folder_message = $this->file_adapter->check_folder( $this->backwpup_adapter->get_plugin_data( 'TEMP' ), true );
		// check log folder.
		$log_folder         = get_site_option( 'backwpup_cfg_logfolder' );
		$log_folder         = $this->file_adapter->get_absolute_path( $log_folder );
		$log_folder_message = $this->file_adapter->check_folder( $log_folder );
		// check backup destinations.
		$job_types      = $this->backwpup_adapter->get_job_types();
		$job_conf_types = $this->option_adapter->get( $jobid, 'type' );
		$creates_file   = false;
		foreach ( $job_types as $id => $job_type_class ) {
			if ( in_array( $id, $job_conf_types, true ) && $job_type_class->creates_file() ) {
				$creates_file = true;
				break;
			}
		}
		if ( $creates_file ) {
			$job_conf_dests = $this->option_adapter->get( $jobid, 'destinations' );
			$destinations   = 0;

			foreach ( $this->backwpup_adapter->get_registered_destinations() as $id => $dest ) {
				if ( ! in_array( $id, $job_conf_dests, true ) || empty( $dest['class'] ) ) {
					continue;
				}
				++$destinations;
			}
		}

		$this->job_adapter->get_jobrun_url( 'runnow', $jobid );

		sleep( 1 ); // Wait for the job to start.
		return new WP_HTTP_Response(
			[
				'status'  => 200,
				'message' => sprintf( __( 'Job "%s" started.', 'backwpup' ), esc_attr( $this->option_adapter->get( $jobid, 'name' ) ) ), // @phpcs:ignore
			],
			200,
			[ 'Content-Type' => 'text/json' ]
			);
	}

	/**
	 * Start backup process for when jobs are deleted
	 *
	 * @return int
	 */
	private function get_job_id_when_no_available_job(): int {
		$job_id = get_site_option( 'backwpup_backup_now_job_id', 0 );
		if ( $job_id < 1 ) {
			$job_id = $this->option_adapter->next_job_id();
			update_site_option( 'backwpup_backup_now_job_id', $job_id );
			$this->option_adapter->update( $job_id, 'type', $this->job_types_adapter->get_type_job_both() );
		}

		$this->backup_now_default_values( $job_id, true );

		return $job_id;
	}

	/**
	 * Generate backup default values
	 *
	 * @param int  $next_job_id The next job id.
	 * @param bool $backup_now Check if it's a temp job or back up now.
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

	/**
	 * Process bulk actions.
	 *
	 * @param WP_REST_Request $request The REST request object containing the parameters.
	 *
	 * @return WP_REST_Response The response object containing the status and message.
	 *
	 * @throws Exception If an error occurs during the process.
	 */
	public function process_bulk_actions( WP_REST_Request $request ): \WP_REST_Response {
		$params   = $request->get_params();
		$response = [
			'success' => [],
			'errors'  => [],
		];
		$action   = $params['action'];
		switch ( $action ) {
			case 'delete':
				$backups = $params['backups'];
				foreach ( $backups as $backup ) {
					try {
						$this->delete_backup( $backup );
						$response['success'][] = $backup;
					} catch ( Exception $e ) {
						$response['errors'][] = [
							'backup' => $backup,
							'error'  => $e->getMessage(),
						];
					}
				}
				$response['message'] = __( 'Bulk action processed.', 'backwpup' );
				break;
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Delete a single backup.
	 *
	 * @param array $backup The backup data.
	 *
	 * @throws Exception If an error occurs during deletion.
	 */
	private function delete_backup( $backup ) {
		if ( empty( $backup['dataset']['data-url'] ) ) {
			throw new Exception( __( 'Invalid backup data.', 'backwpup' ) ); // @phpcs:ignore
		}

		// Parse the dataset URL.
		$query = wp_parse_url( $backup['dataset']['data-url'], PHP_URL_QUERY );
		parse_str( $query, $params );

		// Validate required params.
		if ( empty( $params['jobdest-top'] ) || empty( $params['backupfiles'] ) ) {
			throw new Exception( __( 'Missing required parameters.', 'backwpup' ) ); // @phpcs:ignore
		}

		$jobdest = sanitize_text_field( $params['jobdest-top'] );
		$file    = esc_attr( $params['backupfiles'][0] ); // Handle array format.

		[$jobid, $dest] = explode( '_', $jobdest );
		$dest_class     = $this->backwpup_adapter->get_destination( $dest );

		if ( ! $dest_class ) {
			throw new Exception( __( 'Invalid destination class.', 'backwpup' ) ); // @phpcs:ignore
		}

		// Perform the deletion.
		$dest_class->file_delete( $jobdest, $file );

		/**
		 * Fires after deleting backups.
		 *
		 * @param array $params['backupfiles'] Backup file deleted.
		 * @param string $dest Destination.
		 */
		do_action( 'backwpup_after_delete_backups', $params['backupfiles'], $dest );
	}
}
