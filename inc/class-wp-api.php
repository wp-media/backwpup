<?php
/**
 * The BackWPup WP API class.
 */

use BackWPup\Utils\BackWPupHelpers;
use Inpsyde\BackWPup\Pro\License\Api\LicenseActivation;
use Inpsyde\BackWPup\Pro\License\Api\LicenseDeactivation;
use Inpsyde\BackWPup\Pro\License\Api\LicenseStatusRequest;
use Inpsyde\BackWPup\Pro\License\LicenseSettingUpdater;

class BackWPup_WP_API {

	/**
	 * The authorised blocks type.
	 *
	 * @var array
	 */
	private static array $authorised_blocks_type = [
		'component',
		'children',
	];

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Register the rest API routes.
	 */
	public function register_routes() {
		register_rest_route(
			'backwpup/v1',
			'/backups',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'get_backups_list' ],
				'permission_callback' => function () {
					return current_user_can( 'backwpup' );
				},
			]
		);
		register_rest_route(
			'backwpup/v1',
			'/pagination',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'get_pagination' ],
				'permission_callback' => function () {
					return current_user_can( 'backwpup' );
				},
			]
		);
		register_rest_route(
			'backwpup/v1',
			'/updatejob',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'update_job' ],
				'permission_callback' => function () {
					return current_user_can( 'backwpup' );
				},
			]
		);

		register_rest_route(
			'backwpup/v1',
			'/startbackup',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'start_backup' ],
				'permission_callback' => function () {
					return current_user_can( 'backwpup' );
				},
			]
		);

		register_rest_route(
			'backwpup/v1',
			'/save_database_settings',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'save_database_settings' ],
				'permission_callback' => function () {
					return current_user_can( 'backwpup' );
				},
			]
		);

		register_rest_route(
			'backwpup/v1',
			'/save_files_settings',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'save_files_settings' ],
				'permission_callback' => function () {
					return current_user_can( 'backwpup' );
				},
			]
		);
		register_rest_route(
			'backwpup/v1',
			'/cloudsaveandtest',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'cloud_save_and_test' ],
				'permission_callback' => function () {
					return current_user_can( 'backwpup' );
				},
			]
		);
		register_rest_route(
			'backwpup/v1',
			'/save_site_option',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'save_site_option' ],
				'permission_callback' => function () {
					return current_user_can( 'backwpup' );
				},
			]
		);
		register_rest_route(
			'backwpup/v1',
			'/storagelistcompact',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_storage_list_compact' ],
				'permission_callback' => function () {
					return current_user_can( 'backwpup' );
				},
			]
		);

		register_rest_route(
			'backwpup/v1',
			'/save_excluded_tables',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'save_excluded_tables' ],
				'permission_callback' => function () {
					return current_user_can( 'backwpup' );
				},
			]
		);
		register_rest_route(
			'backwpup/v1',
			'/cloud_is_authenticated',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'cloud_is_authenticated' ],
				'permission_callback' => function () {
					return current_user_can( 'backwpup' );
				},
			]
		);

		register_rest_route(
			'backwpup/v1',
			'/process_bulk_actions',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'process_bulk_actions' ],
				'permission_callback' => function () {
					return current_user_can( 'backwpup' );
				},
			]
			);
		register_rest_route(
			'backwpup/v1',
			'/save_files_exclusions',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'save_files_exclusions' ],
				'permission_callback' => function () {
					return current_user_can( 'backwpup' );
				},
			]
		);
		register_rest_route(
			'backwpup/v1',
			'/authenticate_cloud',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'authenticate_cloud' ],
				'permission_callback' => function () {
					return current_user_can( 'backwpup' );
				},
			]
		);
		register_rest_route(
			'backwpup/v1',
			'/delete_auth_cloud',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'delete_auth_cloud' ],
				'permission_callback' => function () {
					return current_user_can( 'backwpup' );
				},
			]
		);
		register_rest_route(
			'backwpup/v1',
			'/getblock',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'getblock' ],
				'permission_callback' => function () {
					return current_user_can( 'backwpup' );
				},
			]
		);
		register_rest_route(
			'backwpup/v1',
			'/license_update',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'license_update' ],
				'permission_callback' => function () {
					return current_user_can( 'backwpup' );
				},
			]
		);
	}

	/**
	 * Check if the cloud is authenticated.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @throws Exception If the cloud name is not set.
	 * @throws Exception If there is no backup job.
	 * @return WP_HTTP_Response
	 */
	public function cloud_is_authenticated( WP_REST_Request $request ) {
		$params = $request->get_params();
		$job_id = get_site_option( 'backwpup_backup_files_job_id', false );
		$status = 200;
		$html   = '';
		try {
			$files_job_id = get_site_option( 'backwpup_backup_files_job_id', false );
			if ( false === $files_job_id ) {
				throw new Exception( __( 'No backup jobs set.', 'backwpup' ) );
			}
			if ( ! isset( $params['cloud_name'] ) ) {
				throw new Exception( __( 'No cloud name set.', 'backwpup' ) );
			}
			$function_to_call = $params['cloud_name'] . '_is_authenticated';
			$html             = $this->$function_to_call( $job_id );
		} catch ( Exception $e ) {
			$status = 500;
			$html   = BackWPupHelpers::component(
				'alerts/info',
				[
					'type'    => 'alert',
					'font'    => 'xs',
					'content' => $e->getMessage(),
				]
			);
		}
		return new WP_HTTP_Response( $html, $status, [ 'Content-Type' => 'text/html' ] );
	}

	/***
	 * Check if the Google Drive is authenticated.
	 *
	 * @param string $job_id the job id.
	 * @return string
	 */
	private function gdrive_is_authenticated( $job_id ) {
		$refresh_token      = BackWPup_Encryption::decrypt(
			(string) BackWPup_Option::get( $job_id, 'gdriverefreshtoken' )
		);
		$authenticate_label = __( 'Authenticated!', 'backwpup' );
		$type               = 'info';
		if ( empty( $refresh_token ) ) {
			$authenticate_label = __( 'Not authenticated!', 'backwpup' );
			$type               = 'alert';
		}
		$html = BackWPupHelpers::component(
			'alerts/info',
			[
				'type'    => $type,
				'font'    => 's',
				'content' => $authenticate_label,
			]
		);
		return $html;
	}

	/***
	 * Check if the DropBox is authenticated.
	 *
	 * @param string $job_id the job id.
	 * @return string
	 */
	private function dropbox_is_authenticated( $job_id ) {
		$dropboxtoken       = BackWPup_Option::get( $job_id, 'dropboxtoken', [] );
		$authenticate_label = __( 'Authenticated!', 'backwpup' );
		$type               = 'info';
		if ( empty( $dropboxtoken['refresh_token'] ) ) {
			$authenticate_label = __( 'Not authenticated!', 'backwpup' );
			$type               = 'alert';
		}
		$html = BackWPupHelpers::component(
			'alerts/info',
			[
				'type'    => $type,
				'font'    => 's',
				'content' => $authenticate_label,
			]
		);
		return $html;
	}

	/***
	 * Check if the OneDrive is authenticated.
	 *
	 * @param string $job_id the job id.
	 * @return string
	 */
	private function onedrive_is_authenticated( $job_id ) {
		$client_state       = BackWPup_Option::get( $job_id, 'onedrive_client_state' );
		$authenticate_label = __( 'Authenticated!', 'backwpup' );
		$type               = 'info';
		if ( ! isset( $client_state->token->data->access_token ) ) {
			$authenticate_label = __( 'Not authenticated!', 'backwpup' );
			$type               = 'alert';
		}
		$html = BackWPupHelpers::component(
			'alerts/info',
			[
				'type'    => $type,
				'font'    => 's',
				'content' => $authenticate_label,
			]
		);
		return $html;
	}

	/**
	 * Delete an auth cloud configuration.
	 *
	 * This function handles the deletion of an authentication configuration for a specified cloud service.
	 * It checks for the presence of 'cloud_name' in the request parameters and calls the appropriate method
	 * to delete the authentication configuration.
	 *
	 * @param WP_REST_Request $request The REST request object containing the parameters.
	 *
	 * @return WP_HTTP_Response The response object containing the HTML content and status.
	 *
	 * @throws Exception If 'cloud_name' is not set or if an error occurs during the process.
	 */
	public function delete_auth_cloud( WP_REST_Request $request ) {
		$params = $request->get_params();
		$status = 200;
		$html   = '';
		try {
			if ( ! isset( $params['cloud_name'] ) ) {
				throw new Exception( __( 'No cloud name set.', 'backwpup' ) );
			}
			$function_to_call = 'delete_auth_' . $params['cloud_name'];
			$html             = $this->$function_to_call( $request );
		} catch ( Exception $e ) {
			$status = 500;
			$html   = BackWPupHelpers::component(
				'alerts/info',
				[
					'type'    => 'alert',
					'font'    => 'xs',
					'content' => $e->getMessage(),
				]
			);
		}
		return new WP_HTTP_Response( $html, $status, [ 'Content-Type' => 'text/html' ] );
	}

	/**
	 * Delete the Sugarsync authentication.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return string
	 * @throws Exception If there is no backup job.
	 */
	private function delete_auth_sugarsync( WP_REST_Request $request ) {
		$params       = $request->get_params();
		$files_job_id = get_site_option( 'backwpup_backup_files_job_id', false );
		if ( false === $files_job_id ) {
			throw new Exception( esc_html__( 'No backup jobs set.', 'backwpup' ) );
		}
		$jobids = [
			$files_job_id,
			$files_job_id + 1,
		];
		foreach ( $jobids as $jobid ) {
			BackWPup_Option::delete( $jobid, 'sugarrefreshtoken' );
		}
		$html = BackWPupHelpers::children( 'sidebar/sugar-sync-parts/api-connexion' );
		return $html;
	}

	/**
	 * Authenticate the cloud service.
	 *
	 * This function handles the authentication process for a specified cloud service.
	 * It checks for the presence of 'cloud_name' in the request parameters and calls the appropriate method
	 * to authenticate the cloud service.
	 *
	 * @param WP_REST_Request $request The REST request object containing the parameters.
	 *
	 * @return WP_HTTP_Response The response object containing the HTML content and status.
	 *
	 * @throws Exception If 'cloud_name' is not set or if an error occurs during the process.
	 */
	public function authenticate_cloud( WP_REST_Request $request ) {
		$params = $request->get_params();
		$status = 200;
		$html   = '';
		try {
			if ( ! isset( $params['cloud_name'] ) ) {
				throw new Exception( __( 'No cloud name set.', 'backwpup' ) );
			}
			$function_to_call = 'authenticate_' . $params['cloud_name'];
			$html             = $this->$function_to_call( $request );
		} catch ( Exception $e ) {
			$status = 500;
			$html   = BackWPupHelpers::component(
				'alerts/info',
				[
					'type'    => 'alert',
					'font'    => 'xs',
					'content' => $e->getMessage(),
				]
			);
		}
		return new WP_HTTP_Response( $html, $status, [ 'Content-Type' => 'text/html' ] );
	}

	/**
	 * Authenticate the sugarsync cloud.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return string
	 * @throws Exception If the email is not set.
	 * @throws Exception If the password is not set.
	 * @throws Exception If there is no backup job.
	 */
	private function authenticate_sugarsync( WP_REST_Request $request ) {
		$params = $request->get_params();
		if ( ! isset( $params['sugaremail'] ) || '' === $params['sugaremail'] ) {
			throw new Exception( esc_html__( 'No email set.', 'backwpup' ) );
		}
		if ( ! isset( $params['sugarpass'] ) || '' === $params['sugarpass'] ) {
			throw new Exception( esc_html__( 'No password set.', 'backwpup' ) );
		}
		$files_job_id = get_site_option( 'backwpup_backup_files_job_id', false );
		if ( false === $files_job_id ) {
			throw new Exception( esc_html__( 'No backup jobs set.', 'backwpup' ) );
		}
		$jobids        = [
			$files_job_id,
			$files_job_id + 1,
		];
		$sugarsync     = new BackWPup_Destination_SugarSync_API();
		$refresh_token = $sugarsync->get_Refresh_Token( sanitize_email( $params['sugaremail'] ), $params['sugarpass'] );
		if ( ! empty( $refresh_token ) ) {
			foreach ( $jobids as $jobid ) {
				BackWPup_Option::update( $jobid, 'sugarrefreshtoken', $refresh_token );
			}
		}
		$html = BackWPupHelpers::children( 'sidebar/sugar-sync-parts/api-connexion' );
		return $html;
	}

	/**
	 * Get the backups list.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return void
	 */
	public function get_backups_list( WP_REST_Request $request ) {
		$params = $request->get_params();
		// pagination calculation.
		$page   = $params['page'] ?? 1;
		$length = $params['length'] ?? 10;
		$start  = $page * $length - $length;
		// Get the jobs list.
		$jobs_ids                = BackWPup_Option::get_job_ids();
		$backups                 = [];
		$registered_destinations = BackWPup::get_registered_destinations();
		foreach ( $jobs_ids as $a_job_id ) {
			$job      = BackWPup_Option::get_job( $a_job_id );
			$dests    = BackWPup_Option::get( $a_job_id, 'destinations' );
			$job_data = [
				'id'       => $a_job_id,
				'name'     => $job['name'],
				'type'     => $job['activetype'],
				'data'     => [ 'Unknown' ],
				'logfile'  => $job['logfile'],
				'last_run' => $job['lastrun'] ?? null,
			];
			// Get the backups list for that job.
			foreach ( $dests as $dest ) {
				if ( empty( $registered_destinations[ $dest ]['class'] ) ) {
					continue;
				}
				$dest_object = BackWPup::get_destination( $dest );
				$items       = $dest_object->file_get_list( $a_job_id . '_' . $dest );
				array_walk(
					$items,
					function ( &$item ) use ( $job_data, $dest, $page ) {
						$item = array_merge( $item, $job_data, [ 'stored_on' => $dest ] );
						// Parse the filename to get the type of backup.
						$filename = pathinfo( $item['filename'] )['filename'];
						// Remove reluctant file extensions.
						$filename       = preg_replace( '/\.[^.]+$/', '', $filename );
						$filename_parts = explode( '_', $filename );
						if ( isset( $filename_parts[3] ) ) {
							$item['data'] = (array) explode( '-', $filename_parts[3] );
						}
						$local_file      = untrailingslashit( BackWPup::get_plugin_data( 'TEMP' ) ) . "/{$item['filename']}";
						$downloadhref    = '';
						$downloadurl     = '';
						$downloadtrigger = '';
						if ( 'HIDRIVE' === $dest && $item['filesize'] > 10485760 ) {
							$request       = new BackWPup_Pro_Destination_HiDrive_Request();
							$authorization = new BackWPup_Pro_Destination_HiDrive_Authorization( $request );
							$api           = new BackWPup_Pro_Destination_HiDrive_Api( $request, $authorization );
							$response      = $api->temporalDownloadUrl( $job_data['id'], $item['file'] );
							$respons_body  = json_decode( (string) $response['body'] );

							if ( isset( $respons_body->url ) ) {
								$downloadurl     = $respons_body->url;
								$downloadhref    = $respons_body->url;
								$downloadtrigger = 'direct-download-backup';
							}
						} else {
							$downloadurl     = wp_nonce_url( $item['downloadurl'], 'backwpup_action_nonce' );
							$downloadhref    = '#TB_inline?height=300&inlineId=tb_download_file&width=640&height=412';
							$downloadtrigger = 'download-backup';
						}
						// Add the download URL and dataset.
						$item['dataset-download'] = [
							'data-jobid'       => $job_data['id'],
							'data-destination' => $dest,
							'data-file'        => $item['file'],
							'data-local-file'  => $local_file,
							'data-nonce'       => wp_create_nonce( 'backwpup_action_nonce' ),
							'data-url'         => $downloadurl,
							'data-href'        => $downloadhref,
						];
						$item['download-trigger'] = $downloadtrigger;
						// If the user can restore, add the restore URL.
						if ( current_user_can( 'backwpup_restore' ) && ! empty( $item['restoreurl'] ) ) {
							$item['dataset-restore'] = [
								'label'    => __( 'Restore Backup', 'backwpup' ),
								'data-url' => wp_nonce_url(
									add_query_arg(
										[
											'step' => 1,
											'trigger_download' => 1,
										],
										$item['restoreurl']
										),
								'restore-backup_' . $job_data['id']
									),
							];
						} elseif ( current_user_can( 'backwpup_restore' ) ) {
							$item['dataset-restore'] = [
								'label'    => __( 'Restore Backup', 'backwpup' ),
								'data-url' => network_admin_url( 'admin.php?page=backwpuprestore' ),
							];
						}
						// If the user can delete, add the delete URL.
						if ( current_user_can( 'backwpup_backups_delete' ) ) {
							$item['dataset-delete'] = [
								'data-url' => wp_nonce_url(
									add_query_arg(
										[
											'page'        => 'backwpupbackups',
											'action'      => 'delete',
											'jobdest-top' => $job_data['id'] . '_' . $dest,
											'backupfiles[]' => esc_attr( $item['file'] ),
											'paged'       => $page,
										],
										network_admin_url( 'admin.php' )
									),
									'bulk-backups'
								),
							];
						}
					}
					);
				$backups = array_merge( $backups, $items );
			}
		}
		$unique_backups = [];
		foreach ( $backups as $item ) {
			$key = $item['stored_on'] . '|' . $item['filename'];
			if ( ! isset( $seen[ $key ] ) ) {
				$seen[ $key ]     = true;
				$unique_backups[] = $item;
			}
		}
		$backups = $unique_backups;
		// Sort and slice the backups list.
		usort(
			$backups,
			function ( $a, $b ) {
				return $b['time'] <=> $a['time'];
			}
			);
		$nb_totalbackups = count( $backups );
		$backups         = array_slice( $backups, $start, $length );
		$html            = '';
		// Render the backups list.
		foreach ( $backups as $backup ) {
			$html .= BackWPupHelpers::component( 'table-row-backups', [ 'backup' => $backup ], true );
		}
		$html .= BackWPupHelpers::component(
			'form/hidden',
			[
				'name'  => 'nb_backups',
				'value' => $nb_totalbackups,
			],
			true
		);
		if ( ! empty( $html ) ) {
			wp_send_json_success( $html );
		} else {
			wp_send_json_error( __( 'No backups found.', 'backwpup' ) );
		}
	}

	/**
	 * Get the pagination.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return void
	 */
	public function get_pagination( WP_REST_Request $request ) {
		$params    = $request->get_params();
		$page      = $params['page'] ?? 1;
		$max_pages = $params['max_pages'] ?? 10;
		$html      = BackWPupHelpers::component(
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
			wp_send_json_success( $html );
		} else {
			wp_send_json_error( __( 'No pagination found.', 'backwpup' ) );
		}
	}

	/**
	 * Get the storage list compact.
	 *
	 * @return WP_HTTP_Response
	 * @throws Exception If there is not jobs sets.
	 */
	public function get_storage_list_compact() {
		$status = 200;
		try {
			$files_job_id = get_site_option( 'backwpup_backup_files_job_id', false );
			if ( false === $files_job_id ) {
				throw new Exception( __( 'No backup jobs set.', 'backwpup' ) );
			}
			$storage_destination = BackWPup_Option::get( $files_job_id, 'destinations', [] );
			$html                = BackWPupHelpers::component( 'storage-list-compact', [ 'storages' => $storage_destination ] );
		}
		catch ( Exception $e ) {
			$status = 500;
			$html   = BackWPupHelpers::component(
				'alerts/info',
				[
					'type' => 'alert',
					'font' => 'xs',
				"content" => __($e->getMessage(), 'backwpup'), //phpcs:ignore
				]
				);
		}
		return new WP_HTTP_Response( $html, $status, [ 'Content-Type' => 'text/html' ] );
	}

	/**
	 * Update the job.
	 *
	 * @param WP_REST_Request $request
	 * @throws Exception If there is not jobs sets.
	 * @return WP_HTTP_Response
	 */
	public function update_job( WP_REST_Request $request ): WP_HTTP_Response {
		$params = $request->get_params();
		$return = [];
		try {
			$status = 200;
			if ( isset( $params['job_id'] ) && isset( $params['activ'] ) ) {
				// Extract parameters from the request.
				$file_job_id     = get_site_option( 'backwpup_backup_files_job_id', false ); // Get the ID of the file job.
				$database_job_id = get_site_option( 'backwpup_backup_database_job_id', false ); // Get the ID of the database job.
				$activ           = filter_var( $params['activ'], FILTER_VALIDATE_BOOLEAN ); // Determine if the job is being activated or deactivated.
				$type            = $params['type']; // Get the type of job (either 'files' or 'database').

				// CASE 1: Files type job.
				if ( 'files' === $type ) {
					if ( $file_job_id === $database_job_id ) {
						// Case where file job also handles database backups.
						if ( ! $activ ) {
							// Disable the file job, enable a new database job to handle the backup.
							BackWPup_Job::disable_job( $file_job_id );
							BackWPup_Job::rename_job( $file_job_id, BackWPup_JobTypes::$name_job_files ); // Rename the file job.
							$new_database_job_id = $file_job_id + 1; // Use the next ID for the database job.
							BackWPup_Option::update( $file_job_id, 'type', BackWPup_JobTypes::$type_job_files ); // Mark the file job as handling only file backups.
							BackWPup_Job::enable_job( $new_database_job_id ); // Enable the new database job.
							update_site_option( 'backwpup_backup_database_job_id', $new_database_job_id ); // Update the stored database job ID.
							BackWPup_Job::schedule_job( $new_database_job_id ); // Schedule the new database backup job.
						} elseif ( BackWPup_Job::is_job_enabled( $database_job_id ) ) {
							BackWPup_Job::disable_job( $database_job_id ); // Disable the database job.
							update_site_option( 'backwpup_backup_database_job_id', $file_job_id ); // Update the database job ID to point to the file job.
							BackWPup_Option::update( $file_job_id, 'type', BackWPup_JobTypes::$type_job_both ); // Mark the file job to handle both file and database backups.
							BackWPup_Job::enable_job( $file_job_id ); // Enable the file job.
							BackWPup_Job::schedule_job( $file_job_id ); // Schedule the file job.
							BackWPup_Job::rename_job( $file_job_id, BackWPup_JobTypes::$name_job_both ); // Rename the file job.
						} else {
							BackWPup_Job::enable_job( $file_job_id ); // Enable the file job.
							BackWPup_Job::schedule_job( $file_job_id ); // Schedule the file job.
						}
					} else { // @phpcs:ignore
						// Case where file and database jobs are independent.
						if ( $activ ) {
							// Enable file job and disable database job if necessary.
							if ( BackWPup_Job::is_job_enabled( $database_job_id ) ) {
								BackWPup_Option::update( $file_job_id, 'type', BackWPup_JobTypes::$type_job_files );
								if ( BackWPup_Option::get( $file_job_id, 'cron' ) === BackWPup_Option::get( $database_job_id, 'cron' ) ) {
									// If both jobs share the same cron schedule, update file job to handle both backups.
									BackWPup_Option::update( $file_job_id, 'type', BackWPup_JobTypes::$type_job_both );
									update_site_option( 'backwpup_backup_database_job_id', $file_job_id ); // Update the database job ID.
									BackWPup_Job::rename_job( $file_job_id, BackWPup_JobTypes::$name_job_both ); // Rename the file job.
									BackWPup_Job::disable_job( $database_job_id ); // Disable the database job.
								}
							} else {
								// If the file job is not yet enabled, just set it to handle both jobs.
								BackWPup_Option::update( $file_job_id, 'type', BackWPup_JobTypes::$type_job_files );
							}
							BackWPup_Job::enable_job( $file_job_id ); // Enable the file job.
							BackWPup_Job::schedule_job( $file_job_id );
						} elseif ( ! $activ ) {
							// Disable file job if not activated.
							BackWPup_Job::disable_job( $file_job_id );
						}
					}
				}

				// CASE 2: Database type job.
				elseif ( 'database' === $type ) {
					BackWPup_Job::rename_job( $file_job_id + 1, BackWPup_JobTypes::$name_job_database ); // Rename the database job.
					if ( $file_job_id === $database_job_id ) {
						// Case where file job is also the database job.
						if ( ! $activ ) {
							// Disable DB job, make file job only handle file.
							BackWPup_Option::update( $file_job_id, 'type', BackWPup_JobTypes::$type_job_files );
							BackWPup_Job::rename_job( $file_job_id, BackWPup_JobTypes::$name_job_files ); // Rename the file job.

						} elseif ( BackWPup_Job::is_job_enabled( $file_job_id ) ) {
							// Enable DB job, set file to handle both.
							BackWPup_Option::update( $file_job_id, 'type', BackWPup_JobTypes::$type_job_both );
							BackWPup_Job::rename_job( $file_job_id, BackWPup_JobTypes::$name_job_both ); // Rename the file job.
						} else {
							$new_database_job_id = $file_job_id + 1;
							BackWPup_Job::enable_job( $new_database_job_id );
							BackWPup_Job::schedule_job( $new_database_job_id );
						}
					} else { // @phpcs:ignore
						// Case where file and database jobs are independent.
						if ( $activ ) {
							if ( BackWPup_Job::is_job_enabled( $file_job_id ) ) {
								// If file job is enabled, check if both jobs share the same cron schedule.
								if ( BackWPup_Option::get( $file_job_id, 'cron' ) === BackWPup_Option::get( $database_job_id, 'cron' ) ) {
									// If they share the same cron, update the file job to handle both backups.
									BackWPup_Option::update( $file_job_id, 'type', BackWPup_JobTypes::$type_job_both );
									update_site_option( 'backwpup_backup_database_job_id', $file_job_id ); // Update the database job ID.
									BackWPup_Job::rename_job( $file_job_id, BackWPup_JobTypes::$name_job_both ); // Rename the file job.
								} else {
									// If cron schedules differ, just set the file job to handle both jobs.
									BackWPup_Option::update( $file_job_id, 'type', BackWPup_JobTypes::$type_job_files );
									BackWPup_Job::enable_job( $database_job_id );
									BackWPup_Job::schedule_job( $database_job_id );
								}
							} else {
								// If the file job isn't enabled, ensure that the database job is enabled.
								BackWPup_Job::enable_job( $database_job_id );
								BackWPup_Job::schedule_job( $database_job_id );
							}
						} elseif ( ! $activ ) {
							// If not activating the database job, disable it.
							BackWPup_Job::disable_job( $database_job_id );
						}
					}
				}

				// Set response message based on activation status.
				$return['message'] = $activ
					? sprintf(
						__( 'Backup scheduled at %1$s by WP-Cron', 'backwpup' ), // phpcs:ignore
						date_i18n( get_option( 'date_format' ), time(), true ),
						date_i18n( get_option( 'H:i' ), time(), true )
						) // If activated, show backup schedule details.
					: __( 'No backup scheduled', 'backwpup' ); // phpcs:ignore If not activated, show no backup message. 
			} else {
				$files_job_id = get_site_option( 'backwpup_backup_files_job_id', false );
				if ( false === $files_job_id ) {
					throw new Exception( __( 'Files job not found', 'backwpup' ) );
				}
				$jobs = [
					$files_job_id,
					$files_job_id + 1,
				];
				foreach ( $jobs as $a_job ) {
					if ( isset( $params['storage_destinations'] ) ) {
						$params['storage_destinations'] = array_filter( $params['storage_destinations'] );
						// Update the destination and storage part for each jobs.
						BackWPup_Option::update( $a_job, 'destinations', $params['storage_destinations'] );
					}
				}
				$return['message'] = __( 'Backup updated.', 'backwpup' );
			}
		} catch ( Exception $e ) {
			$status          = 500;
			$return['error'] = $e->getMessage();
		}

		return new WP_HTTP_Response( $return, $status, [ 'Content-Type' => 'text/json' ] );
	}

	/**
	 * Save and test the cloud connection.
	 *
	 * @param WP_REST_Request $request
	 * @throws Exception If the cloud name is not set or the cloud is not found.
	 * @return WP_HTTP_Response
	 */
	public function cloud_save_and_test( WP_REST_Request $request ) {
		$params = $request->get_params();

		$return = [];
		$status = 200;
		try {
			if ( ! isset( $params['cloud_name'] ) || '' === $params['cloud_name'] ) {
				throw new Exception( __( 'Cloud not set', 'backwpup' ) );
			}
			$cloud = BackWPup::get_destination( $params['cloud_name'] );
			if ( null === $cloud ) {
				throw new Exception( __( 'Cloud not found', 'backwpup' ) );
			}
			$files_job_id = get_site_option( 'backwpup_backup_files_job_id', false );
			if ( false === $files_job_id ) {
				throw new Exception( __( 'Files job not found', 'backwpup' ) );
			}
			$jobs                = [
				$files_job_id,
				$files_job_id + 1,
			];
			$should_be_connected = true;
			if ( isset( $params['delete_auth'] ) && 'true' === $params['delete_auth'] ) {
				$should_be_connected = false;
			}
			$cloud->edit_form_post_save( $jobs );
			if ( $should_be_connected !== $cloud->can_run( BackWPup_Option::get_job( $files_job_id ) ) ) {
				throw new Exception( __( 'Connection failed', 'backwpup' ) );
			}
			$return['message']   = __( 'Connection successful', 'backwpup' );
			$return['connected'] = $should_be_connected;
		}
		catch ( Exception $e ) {
			$status          = 500;
			$return['error'] = $e->getMessage();
		}
		return new WP_HTTP_Response( $return, $status, [ 'Content-Type' => 'text/json' ] );
	}


	/**
	 * Starts the backup process.
	 *
	 * This function is triggered by a WP REST API request to initiate a backup.
	 * If a job ID is provided, it will start the backup for that job. Otherwise, it will start the backup for the file job.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error The response object on success, or WP_Error on failure.
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

		if ( isset( $params['job_id'] ) ) {
			$jobid = $params['job_id'];
		} else {
			$jobid = get_site_option( 'backwpup_backup_files_job_id', false );
		}

		// check temp folder.
		$temp_folder_message = BackWPup_File::check_folder( BackWPup::get_plugin_data( 'TEMP' ), true );
		// check log folder.
		$log_folder         = get_site_option( 'backwpup_cfg_logfolder' );
		$log_folder         = BackWPup_File::get_absolute_path( $log_folder );
		$log_folder_message = BackWPup_File::check_folder( $log_folder );
		// check backup destinations.
		$job_types      = BackWPup::get_job_types();
		$job_conf_types = BackWPup_Option::get( $jobid, 'type' );
		$creates_file   = false;

		foreach ( $job_types as $id => $job_type_class ) {
			if ( in_array( $id, $job_conf_types, true ) && $job_type_class->creates_file() ) {
				$creates_file = true;
				break;
			}
		}
		if ( $creates_file ) {
			$job_conf_dests = BackWPup_Option::get( $jobid, 'destinations' );
			$destinations   = 0;

			foreach ( BackWPup::get_registered_destinations() as $id => $dest ) {
				if ( ! in_array( $id, $job_conf_dests, true ) || empty( $dest['class'] ) ) {
					continue;
				}
				++$destinations;
			}
		}

		BackWPup_Job::get_jobrun_url( 'runnow', $jobid );

		sleep( 1 ); // Wait for the job to start.
		return new WP_HTTP_Response(
			[
				'status'  => 200,
				'message' => sprintf( __( 'Job "%s" started.', 'backwpup' ), esc_attr( BackWPup_Option::get( $jobid, 'name' ) ) ), // @phpcs:ignore
			],
			200,
			[ 'Content-Type' => 'text/json' ]
			);
	}

	/**
	 * Save settings via REST API.
	 *
	 * This method handles the saving of backup settings based on the provided
	 * parameters from the WP_REST_Request. It updates the cron expression and job types
	 * for the backup jobs accordingly.
	 *
	 * @param WP_REST_Request $request The REST request object containing the parameters.
	 * @param string          $job_type The type of job (database or files).
	 *
	 * @return WP_HTTP_Response The response object containing the status and message.
	 *
	 * @throws Exception If an error occurs during the process.
	 */
	private function save_settings( WP_REST_Request $request, string $job_type ): WP_HTTP_Response {
		$params = $request->get_params();
		$return = [];

		try {
			$frequency                   = $params['frequency'];
			$params['start_time']        = isset( $params['start_time'] ) ? $params['start_time'] : '00:00';
			$params['hourly_start_time'] = isset( $params['hourly_start_time'] ) ? (int) $params['hourly_start_time'] : 0;
			$day_of_week                 = (int) isset( $params['day_of_week'] ) ? $params['day_of_week'] : 0;
			$day_of_month                = isset( $params['day_of_month'] ) ? $params['day_of_month'] : '';
			$start_time                  = explode( ':', $params['start_time'] );

			if ( 'hourly' === $frequency ) {
				$start_time = [ '*', $params['hourly_start_time'] ];
			}

			$new_cron_expression = BackWPup_Cron::get_basic_cron_expression( $frequency, $start_time[0], $start_time[1], $day_of_week, $day_of_month );

			// Map job IDs based on type.
			$job_ids = [
				'database' => get_site_option( 'backwpup_backup_database_job_id', false ),
				'files'    => get_site_option( 'backwpup_backup_files_job_id', false ),
			];

			// Current and other job types.
			$other_job = $job_ids[ 'database' === $job_type ? 'files' : 'database' ];

			// Get options for the jobs.
			$other_job_cron = BackWPup_Option::get( $other_job, 'cron' );

			// Determine job behavior based on cron expressions.
			if ( $new_cron_expression === $other_job_cron ) {
				$job_ids = $this->set_combined_job( $job_ids );
			} else {
				$job_ids = $this->set_separate_jobs( $job_ids );
			}

			// Update cron and re-evaluate jobs.
			BackWPup_Option::update( $job_ids[ $job_type ], 'cron', $new_cron_expression );
			BackWPup_Job::schedule_job( $job_ids[ $job_type ] );
			$cron_next = BackWPup_Cron::cron_next( $new_cron_expression );

			$return['next_backup'] = sprintf(
				__( '%1$s at %2$s by WP-Cron', 'backwpup' ), // @phpcs:ignore
				date_i18n( get_option( 'date_format' ), $cron_next, true ),
				date_i18n( 'H:i', $cron_next, true )
			); // @phpcs:ignore
			$return['status']      = 200;
			$return['message'] = __(ucfirst($job_type) . ' settings saved successfully.', 'backwpup'); // @phpcs:ignore
		} catch ( Exception $e ) {
			$return['status'] = 500;
			$return['error']  = $e->getMessage();
		}

		return new WP_HTTP_Response( $return, $return['status'], [ 'Content-Type' => 'text/json' ] );
	}

	/**
	 * Set the jobs to a "combined" type.
	 *
	 * @param array $job_ids         Array of job IDs for reference.
	 */
	private function set_combined_job( $job_ids ) {
		BackWPup_Job::disable_job( $job_ids['database'] ); // Disable the database job.
		$job_ids['database'] = $job_ids['files']; // Update the database job ID in the array.

		update_site_option( 'backwpup_backup_database_job_id', $job_ids['database'] ); // Update the database job ID to point to the file job.
		BackWPup_Option::update( $job_ids['files'], 'type', BackWPup_JobTypes::$type_job_both ); // Mark the file job to handle both file and database backups.
		BackWPup_Job::schedule_job( $job_ids['files'] ); // Schedule the file job.
		BackWPup_Job::rename_job( $job_ids['files'], BackWPup_JobTypes::$name_job_both ); // Rename the file job.

		return $job_ids;
	}

	/**
	 * Set the jobs to a "separate" type.
	 *
	 * @param array $job_ids         Array of job IDs for reference.
	 */
	private function set_separate_jobs( $job_ids ) {
		$job_ids['database'] = $job_ids['files'] + 1; // Update the database job ID in the array.
		update_site_option( 'backwpup_backup_database_job_id', $job_ids['database'] ); // Update the database job ID to point to the file job.
		BackWPup_Option::update( $job_ids['files'], 'type', BackWPup_JobTypes::$type_job_files ); // Mark the file job to handle only file backups.
		BackWPup_Job::rename_job( $job_ids['files'], BackWPup_JobTypes::$name_job_files ); // Rename the file job.
		if ( ! BackWPup_Job::is_job_enabled( $job_ids['database'] ) ) {
			BackWPup_Job::enable_job( $job_ids['database'] ); // Enable the database job.
			BackWPup_Job::schedule_job( $job_ids['database'] ); // Schedule the database job.
		}
		return $job_ids;
	}

	/**
	 * Save site option via REST API.
	 *
	 * This method handles the saving of site options based on the provided parameters
	 * from the WP_REST_Request. It updates the site options accordingly.
	 * The value is encrypted if the secure flag is set to true.
	 * Exemple : {"option_name":{"value":"thevalue","secure":false}}
	 *
	 * @param WP_REST_Request $request The REST request object containing the parameters.
	 *
	 * @return WP_HTTP_Response The response object containing the status and message.
	 */
	public function save_site_option( WP_REST_Request $request ) {
		$params = $request->get_params();
		$return = [];
		$status = 200;
		try {
			foreach ( $params as $key => $values ) {
				if ( '' !== trim( $values['value'] ) ) {
					$value = sanitize_text_field( $values['value'] );
					if ( isset( $values['secure'] ) && true === filter_var( $values['secure'], FILTER_VALIDATE_BOOLEAN ) ) {
						$value = BackWPup_Encryption::encrypt( sanitize_text_field( $value ) );
					}
					update_site_option( $key, $value );
				} else {
					delete_site_option( $key );
				}
				$return['message'] = __( 'Site option saved successfully.', 'backwpup' );
			}
		} catch ( Exception $e ) {
			$status          = 500;
			$return['error'] = $e->getMessage();
		}
		return new WP_HTTP_Response( $return, $status, [ 'Content-Type' => 'text/json' ] );
	}

	/**
	 * Process bulk actions.
	 *
	 * @param WP_REST_Request $request The REST request object containing the parameters.
	 *
	 * @return WP_HTTP_Response The response object containing the status and message.
	 *
	 * @throws Exception If an error occurs during the process.
	 */
	public function process_bulk_actions( WP_REST_Request $request ) {
		$params   = $request->get_params();
		$response = [
			'success' => [],
			'errors'  => [],
		];
		try {
			$status = 200;
			$action = $params['action'];
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
				default:
					throw new Exception( __( 'Invalid action.', 'backwpup' ) );
			}
		} catch ( Exception $e ) {
			$status            = 500;
			$response['error'] = $e->getMessage();
		}

		return new WP_HTTP_Response( $response, $status, [ 'Content-Type' => 'application/json' ] );
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
		$dest_class     = BackWPup::get_destination( $dest );

		if ( ! $dest_class ) {
			throw new Exception( __( 'Invalid destination class.', 'backwpup' ) ); // @phpcs:ignore
		}

		// Perform the deletion.
		$dest_class->file_delete( $jobdest, $file );
	}

	/**
	 * Save database settings via REST API.
	 *
	 * @param WP_REST_Request $request The REST request object containing the parameters.
	 *
	 * @return WP_HTTP_Response The response object containing the status and message.
	 *
	 * @throws Exception If an error occurs during the process.
	 */
	public function save_database_settings( WP_REST_Request $request ) {
		return $this->save_settings( $request, 'database' );
	}

	/**
	 * Save file settings via REST API.
	 *
	 * @param WP_REST_Request $request The REST request object containing the parameters.
	 *
	 * @return WP_HTTP_Response The response object containing the status and message.
	 *
	 * @throws Exception If an error occurs during the process.
	 */
	public function save_files_settings( WP_REST_Request $request ) {
		return $this->save_settings( $request, 'files' );
	}

	/**
	 * Save excluded tables via REST API.
	 *
	 * This method handles the saving of excluded database tables based on the provided
	 * parameters from the WP_REST_Request. It updates the excluded tables for both jobs
	 * (backwpup_backup_files_job_id and backwpup_backup_files_job_id + 1).
	 *
	 * @param WP_REST_Request $request The REST request object containing the parameters.
	 *
	 * @return WP_HTTP_Response The response object containing the status and message.
	 *
	 * @throws Exception If an error occurs during the process.
	 */
	public function save_excluded_tables( WP_REST_Request $request ) {
		$params = $request->get_params();
		$return = [];
		try {
			$job_id = $params['job_id'];
			$tables = $params['tabledb'];

			$jobs = [
				$job_id,
				$job_id + 1,
			];

			$job_types = BackWPup::get_job_types();
			foreach ( $jobs as $a_job ) {
				if ( isset( $job_types['DBDUMP'] ) ) {
					$job_types['DBDUMP']->edit_form_post_save( $a_job );
				}
			}

			$return['status']  = 200;
			$return['message'] = __( 'Excluded tables saved successfully.', 'backwpup' );
		} catch ( Exception $e ) {
			$return['status'] = 500;
			$return['error']  = $e->getMessage();
		}

		return new WP_HTTP_Response( $return, $return['status'], [ 'Content-Type' => 'text/json' ] );
	}

	/**
	 * Save files exclusions via REST API.
	 *
	 * This method handles the saving of file exclusions based on the provided
	 * parameters from the WP_REST_Request. It updates the file exclusions for the job.
	 *
	 * @param WP_REST_Request $request The REST request object containing the parameters.
	 *
	 * @return WP_HTTP_Response The response object containing the status and message.
	 *
	 * @throws Exception If an error occurs during the process.
	 */
	public function save_files_exclusions( WP_REST_Request $request ) {
		$params = $request->get_params();
		$return = [];
		try {
			$job_ids =
			[
				get_site_option( 'backwpup_backup_files_job_id', false ),
				get_site_option( 'backwpup_backup_files_job_id', false ) + 1,
			];
			foreach ( $job_ids as $job_id ) {
				if ( false === $job_id ) {
					throw new Exception( __( 'Files job not found', 'backwpup' ) );
				}
				$job_types = BackWPup::get_job_types();
				if ( isset( $job_types['FILE'] ) ) {
					$job_types['FILE']->edit_form_post_save( $job_id );
				}

				$return['status']  = 200;
				$return['message'] = __( 'File exclusions saved successfully.', 'backwpup' );
			}
		} catch ( Exception $e ) {
			$return['status'] = 500;
			$return['error']  = $e->getMessage();
		}

		return new WP_HTTP_Response( $return, $return['status'], [ 'Content-Type' => 'text/json' ] );
	}

	/**
	 * Updates the license by handling the request from the WordPress REST API.
	 *
	 * @param WP_REST_Request $request The REST request object containing the parameters.
	 *
	 * @return WP_HTTP_Response The response object containing the status and message.
	 *
	 * @throws Exception If an error occurs during the process.
	 */
	public function license_update( WP_REST_Request $request ) {
		$params = $request->get_params();
		$return = [];
		$status = 200;
		try {
			if ( ! BackWPup::is_pro() ) {
				throw new Exception( __( 'This feature is only available in the Pro version.', 'backwpup' ) );
			}
			$plugin_data     = [
				'version'    => BackWPup::get_plugin_data( 'version' ),
				'pluginName' => 'backwpup-pro/backwpup.php',
				'slug'       => 'backwpup',
			];
			$activate        = new LicenseActivation( $plugin_data );
			$deactivate      = new LicenseDeactivation( $plugin_data );
			$license_status  = new LicenseStatusRequest();
			$license_updater = new LicenseSettingUpdater(
				$activate,
				$deactivate,
				$license_status
			);
			$message         = $license_updater->update();

			if ( isset( $message['error'] ) ) {
				throw new Exception( $message['error'] );
			}

			$return_message    = $message['message'] ?? $message['activations_remaining'];
			$return['message'] = 'License updated : ' . esc_html( $return_message );
		} catch ( Exception $e ) {
			$status          = 500;
			$return['error'] = $e->getMessage();
		}
		return new WP_HTTP_Response( $return, $status, [ 'Content-Type' => 'text/json' ] );
	}


	/**
	 * Get the block content.
	 *
	 * This function retrieves the HTML content for a specified block based on the provided parameters.
	 * It checks for the presence of 'block_name' and 'block_type' in the request parameters and
	 * calls the appropriate method to get the block content.
	 *
	 * @param WP_REST_Request $request The REST request object containing the parameters.
	 *
	 * @return WP_HTTP_Response The response object containing the HTML content and status.
	 *
	 * @throws Exception If an error occurs during the process.
	 */
	public function getblock( WP_REST_Request $request ) {
		$params = $request->get_params();
		$status = 200;
		$html   = '';
		try {
			if ( ! isset( $params['block_name'] ) ) {
				throw new Exception( __( 'No block name set.', 'backwpup' ) );
			}
			if ( ! isset( $params['block_type'] ) || ! in_array( $params['block_type'], self::$authorised_blocks_type, true ) ) {
				throw new Exception( __( 'Wrong block type set.', 'backwpup' ) );
			}
			$method = $params['block_type'];
			if ( 'component' === $method ) {
				$data = $params['block_data'] ?? [];
				$html = BackWPupHelpers::$method( $params['block_name'], $data );
			} else {
				$html = BackWPupHelpers::$method( $params['block_name'] );
			}
		} catch ( Exception $e ) {
			$status = 500;
			$html   = BackWPupHelpers::component(
				'alerts/info',
				[
					'type'    => 'alert',
					'font'    => 'xs',
					'content' => $e->getMessage(),
				]
			);
		}
		return new WP_HTTP_Response( $html, $status, [ 'Content-Type' => 'text/html' ] );
	}
}
