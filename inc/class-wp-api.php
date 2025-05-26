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
	 * Init hooks.
	 */
	public function init() {
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
			'/getjobslist',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_jobs_list' ],
				'permission_callback' => function () {
					return current_user_can( 'backwpup' );
				},
			]
		);
		register_rest_route(
			'backwpup/v1',
			'/addjob',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'add_job' ],
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
			'/save_job_settings',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'save_job_settings' ],
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

		register_rest_route(
			'backwpup/v1',
			'/delete_job',
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'delete_job' ],
				'permission_callback' => function () {
					return current_user_can( 'backwpup' );
				},
			]
		);

		register_rest_route(
			'backwpup/v1',
			'/update-job-title',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'update_job_title' ],
				'permission_callback' => function () {
					return current_user_can( 'backwpup' );
				},
				'args'                => [
					'job_id' => [
						'required'          => true,
						'validate_callback' => function ( $param ) {
							if ( ! is_numeric( $param ) ) {
								return false;
							}

							return $param > 0;
						},
						'sanitize_callback' => 'absint',
					],
					'title'  => [
						'required'          => true,
						'validate_callback' => function ( $param ) {
							if ( ! is_string( $param ) ) {
								return false;
							}

							return ! empty( trim( $param ) );
						},
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);
		register_rest_route(
			'backwpup/v1',
			'/backupnow',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'backup_now' ],
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
		return rest_ensure_response( $html );
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
		$params = $request->get_params();
		if ( isset( $params['job_id'] ) ) {
			$jobids = [ $params['job_id'] ];
		} else {
			$files_job_id = get_site_option( 'backwpup_backup_files_job_id', false );
			if ( false === $files_job_id ) {
				throw new Exception( esc_html__( 'No backup jobs set.', 'backwpup' ) );
			}
			$jobids = [
				$files_job_id,
				$files_job_id + 1,
			];
		}

		foreach ( $jobids as $jobid ) {
			BackWPup_Option::delete( $jobid, 'sugarrefreshtoken' );
		}
		$html = BackWPupHelpers::children( 'sidebar/sugar-sync-parts/api-connexion', true, [ 'job_id' => $jobids[0] ] );
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
		return rest_ensure_response( $html );
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
		if ( isset( $params['job_id'] ) ) {
			$jobs_ids = [ $params['job_id'] ];
		} else {
			$files_job_id = get_site_option( 'backwpup_backup_files_job_id', false );
			if ( false === $files_job_id ) {
				throw new Exception( esc_html__( 'No backup jobs set.', 'backwpup' ) );
			}
			$jobs_ids = [
				$files_job_id,
				$files_job_id + 1,
			];
		}

		$sugarsync     = new BackWPup_Destination_SugarSync_API();
		$refresh_token = $sugarsync->get_Refresh_Token( sanitize_email( $params['sugaremail'] ), $params['sugarpass'] );
		if ( ! empty( $refresh_token ) ) {
			foreach ( $jobs_ids as $jobid ) {
				BackWPup_Option::update( $jobid, 'sugarrefreshtoken', $refresh_token );
			}
		}
		$html = BackWPupHelpers::children( 'sidebar/sugar-sync-parts/api-connexion', true, [ 'job_id' => $jobs_ids[0] ] );
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
			$job = BackWPup_Option::get_job( $a_job_id );
			if ( ! $job ) {
				continue;
			}
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
				$items       = BackWPupHelpers::process_backup_items( $items, $job_data, $dest, $page );
				$backups     = array_merge( $backups, $items );
			}
		}
		if ( 0 !== count( $jobs_ids ) ) {
			// Retrieve The default location backup files and add them to the list.
			$default_location = BackWPup::get_destination( 'FOLDER' );
			$items            = $default_location->file_get_list();
			$items            = BackWPupHelpers::process_backup_items( $items, $job_data, 'FOLDER', $page );
			$backups          = array_merge( $backups, $items );
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
			if ( 'wpcron' === $backup['type'] ) {
				$backup['type'] = __( 'Scheduled', 'backwpup' );
			}

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
	 * Get jobs list in HTML
	 *
	 * @return WP_REST_Response
	 */
	public function get_jobs_list(): WP_REST_Response {
		$jobs = BackWPup_Job::get_jobs();

		$html = '';

		foreach ( $jobs as $job ) {
			// Skip temp jobs.
			if ( isset( $job['tempjob'] ) && true === $job['tempjob'] ) {
				continue;
			}

			if ( isset( $job['backup_now'] ) && true === $job['backup_now'] ) {
				continue;
			}

			// Skip legacy jobs.
			if ( ! isset( $job['jobid'] ) || ( isset( $job['legacy'] ) && true === $job['legacy'] ) ) {
				continue;
			}
			$html .= BackWPupHelpers::component( 'job-item', [ 'job' => $job ], true );
		}

		return rest_ensure_response( $html );
	}

	/**
	 * Add a new backup job.
	 *
	 * @param WP_Rest_Request $request The request object.
	 *
	 * @return WP_Rest_Response
	 */
	public function add_job( WP_Rest_Request $request ): WP_REST_Response {
		$params = $request->get_params();

		$default_values = BackWPup_Option::defaults_job();

		$type   = sanitize_text_field( $params['type'] );
		$job_id = BackWPup_Option::next_job_id();

		$job = [
			'activ' => true,
			'jobid' => $job_id,
		];

		switch ( $type ) {
			case 'files':
				$job['type'] = BackWPup_JobTypes::$type_job_files;
				$job['name'] = BackWPup_JobTypes::$name_job_files;
				break;
			case 'database':
				$job['type'] = BackWPup_JobTypes::$type_job_database;
				$job['name'] = BackWPup_JobTypes::$name_job_database;
				break;
		}

		$job = wp_parse_args( $job, $default_values );

		foreach ( $job as $key => $value ) {
			BackWPup_Option::update( $job_id, $key, $value );
		}

		$cron_next = BackWPup_Cron::cron_next( $job['cron'] );

		wp_schedule_single_event( $cron_next, 'backwpup_cron', [ 'arg' => $job_id ] );

		return rest_ensure_response(
			[
				'success' => true,
				'message' => __( 'You scheduled a new backup successfully!<br>Now you can configure it as you wish.', 'backwpup' ),
			]
			);
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
				$job_id = (int) $params['job_id']; // The job ID.
				$activ  = filter_var( $params['activ'], FILTER_VALIDATE_BOOLEAN ); // Determine if the job is being activated or deactivated.

				if ( ! $activ ) {
					BackWPup_Job::disable_job( $params['job_id'] );
					$next_backup_label = __( 'No backup scheduled', 'backwpup' );

				} else {
					BackWPup_Job::enable_job( $params['job_id'] );
					BackWPup_Job::schedule_job( $params['job_id'] );
					$cron_next         = BackWPup_Cron::cron_next( BackWPup_Option::get( $params['job_id'], 'cron' ) );
					$next_backup_label = sprintf(
						// translators: %1$s = date, %2$s = time.
						__( '%1$s at %2$s', 'backwpup' ),
						wp_date( get_option( 'date_format' ), $cron_next ),
						wp_date( get_option( 'time_format' ), $cron_next )
					);
				}

				// Set response message based on activation status.
				$return['message'] = $next_backup_label;
			} else {
				// If there is a job ID and storage destinations, update the storage destinations just for this id.
				if ( isset( $params['job_id'] ) && isset( $params['storage_destinations'] ) ) {
					$jobs = [ $params['job_id'] ];
				} else {
					$files_job_id = get_site_option( 'backwpup_backup_files_job_id', false );
					if ( false === $files_job_id ) {
						throw new Exception( __( 'Files job not found', 'backwpup' ) );
					}
					$jobs = [
						$files_job_id,
						$files_job_id + 1,
					];
				}
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
	 * Updates the job title.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_job_title( WP_REST_Request $request ) {
		$params = $request->get_params();
		$title  = $params['title'];

		BackWPup_Job::rename_job( $params['job_id'], $title );

		return rest_ensure_response(
			[
				'code'    => 'success',
				'message' => __( 'Job title updated successfully.', 'backwpup' ),
				'data'    => [
					'title' => ucfirst( $title ),
				],
			]
		);
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
			// If no job ID is set, it's from onboarding so we use the files job and DB job.
			if ( ! isset( $params['job_id'] ) || '' === $params['job_id'] ) {
				$files_job_id        = get_site_option( 'backwpup_backup_files_job_id', false );
				$database_job_id     = get_site_option( 'backwpup_backup_database_job_id', false );
				$first_backup_job_id = get_site_option( 'backwpup_first_backup_job_id', false );
				if ( false === $files_job_id || false === $database_job_id || false === $first_backup_job_id ) {
					throw new Exception( __( 'Files job not found', 'backwpup' ) );
				}
				$jobs = [
					$files_job_id,
					$database_job_id,
					$first_backup_job_id,
				];
			} else {
				$jobs = [ $params['job_id'] ];
			}

			$should_be_connected = true;
			if ( isset( $params['delete_auth'] ) && 'true' === $params['delete_auth'] ) {
				$should_be_connected = false;
			}
			$cloud->edit_form_post_save( $jobs );
			if ( $should_be_connected !== $cloud->can_run( BackWPup_Option::get_job( $jobs[0] ) ) ) {
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
		$jobs                  = BackWPup_Job::get_jobs();
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
	 * Save the job frequency settings via the REST API.
	 *
	 * This function updates the cron schedule for a given job and reschedules it accordingly.
	 * It generates a new cron expression based on user input and stores it in the database.
	 *
	 * @param WP_REST_Request $request The REST API request containing job settings.
	 *
	 * @return WP_HTTP_Response The response containing the updated job schedule or an error message.
	 */
	public function save_job_settings( WP_REST_Request $request ) {
		$params = $request->get_params(); // Get request parameters.
		$return = [];

		try {
			// Extract parameters from the request.
			$job_id    = $params['job_id'];
			$frequency = $params['frequency'];

			// Set default values if parameters are not provided.
			$params['start_time']        = isset( $params['start_time'] ) ? $params['start_time'] : '00:00';
			$params['hourly_start_time'] = isset( $params['hourly_start_time'] ) ? (int) $params['hourly_start_time'] : 0;
			$day_of_week                 = (int) isset( $params['day_of_week'] ) ? $params['day_of_week'] : 0;
			$day_of_month                = isset( $params['day_of_month'] ) ? $params['day_of_month'] : '';

			// Convert start_time into hour and minute parts.
			$start_time = explode( ':', $params['start_time'] );

			// Adjust start time for hourly jobs.
			if ( 'hourly' === $frequency ) {
				$start_time = [ '*', $params['hourly_start_time'] ];
			}

			// Generate new cron expression based on the selected frequency and time settings.
			$new_cron_expression = BackWPup_Cron::get_basic_cron_expression(
				$frequency,
				$start_time[0],
				$start_time[1],
				$day_of_week,
				$day_of_month
			);

			// Update the cron expression in the job settings.
			BackWPup_Option::update( $job_id, 'cron', $new_cron_expression );

			// Re-schedule the job with the updated cron schedule.
			BackWPup_Job::schedule_job( $job_id );

			// Get the next scheduled execution time.
			$cron_next = BackWPup_Cron::cron_next( $new_cron_expression );

			// Prepare response with next backup schedule.
			$return['next_backup'] = sprintf(
				// translators: %1$s = date, %2$s = time.
				__( '%1$s at %2$s', 'backwpup' ),
				wp_date( get_option( 'date_format' ), $cron_next ),
				wp_date( get_option( 'time_format' ), $cron_next )
			);

			$return['status']  = 200;
			$return['message'] = __('Job settings saved successfully.', 'backwpup'); // @phpcs:ignore

		} catch ( Exception $e ) {
			// Handle errors.
			$return['status'] = 500;
			$return['error']  = $e->getMessage();
		}

		// Return response as a valid WP REST API response.
		return rest_ensure_response( $return );
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

			$job_types = BackWPup::get_job_types();
			if ( isset( $job_types['DBDUMP'] ) ) {
				$job_types['DBDUMP']->edit_form_post_save( $job_id, $params );
			}

			$return['status']  = 200;
			$return['message'] = __( 'Excluded tables saved successfully.', 'backwpup' );
		} catch ( Exception $e ) {
			$return['status'] = 500;
			$return['error']  = $e->getMessage();
		}

		return rest_ensure_response( $return );
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
			if ( false === $params['job_id'] ) {
				throw new Exception( __( 'Files job not found', 'backwpup' ) );
			}
			$job_types = BackWPup::get_job_types();
			if ( isset( $job_types['FILE'] ) ) {
				$job_types['FILE']->edit_form_post_save( $params['job_id'], $params );
			}
			$return['status']  = 200;
			$return['message'] = __( 'File exclusions saved successfully.', 'backwpup' );
		} catch ( Exception $e ) {
			$return['status'] = 500;
			$return['error']  = $e->getMessage();
		}

		return rest_ensure_response( $return );
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
			$data   = $params['block_data'] ?? [];
			if ( 'component' === $method ) {
				$html = BackWPupHelpers::$method( $params['block_name'], $data, true );
			} else {
				$html = BackWPupHelpers::$method( $params['block_name'],  true, $data );
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
		return rest_ensure_response( $html );
	}

	/**
	 * Delete a job.
	 *
	 * @param WP_REST_Request $request The REST request object containing the parameters.
	 *
	 * @return WP_REST_Response The response object containing the status and message.
	 *
	 * @throws Exception If an error occurs during the process.
	 */
	public function delete_job( WP_REST_Request $request ) {
		$params = $request->get_params();
		$return = [
			'success' => true,
			'message' => __( 'Job deleted successfully.', 'backwpup' ),
		];
		$status = 200;

		if ( ! isset( $params['job_id'] ) ) {

			return new WP_Error( 'missing_job_id', __( 'Job ID not set', 'backwpup' ), [ 'status' => 400 ] );
		}

		$job_id = (int) $params['job_id'];
		if ( ! BackWPup_Job::delete_job( $job_id ) ) {
			$return['success'] = false;
			$return['message'] = __( 'Failed to delete job', 'backwpup' );
		}

		$response = rest_ensure_response( $return );
		$response->set_status( $status );
		return $response;
	}

	/**
	 * Start backup process for when jobs are deleted
	 *
	 * @return int
	 */
	private function get_job_id_when_no_available_job(): int {
		$job_id = get_site_option( 'backwpup_backup_now_job_id', 0 );
		if ( $job_id < 1 ) {
			$job_id = BackWPup_Option::next_job_id();
			update_site_option( 'backwpup_backup_now_job_id', $job_id );
			BackWPup_Option::update( $job_id, 'type', BackWPup_JobTypes::$type_job_both );
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

		$default_values                    = BackWPup_Option::defaults_job();
		$default_destination_folder_values = BackWPup::get_destination( 'FOLDER' )->option_defaults();

		$bwp_job_values = array_merge( $default_values, $default_destination_folder_values, $backup_now_job_values );

		if ( 0 < count( BackWPup_Option::get_job_ids() ) ) {
			$bwp_job_values['archiveformat'] = BackWPup_Option::get( BackWPup_Option::get_job_ids()[0], 'archiveformat' );
		}

		foreach ( $bwp_job_values as $key => $value ) {
			BackWPup_Option::update( $next_job_id, $key, $value );
		}
	}
	/**
	 * Create a new temporary job for the backup now and triger start_backup method with it's id.
	 *
	 * @param WP_REST_Request $request The REST request object containing the parameters.
	 * @return WP_REST_Response
	 * @throws Exception If Error occurs during the process.
	 */
	public function backup_now( WP_REST_Request $request ) {
		$params = $request->get_params();
		$return = [];
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
		try {
			// Create a new temporary job for the backup now.
			$next_jobid = BackWPup_Option::next_job_id();
			$this->backup_now_default_values( $next_jobid );

			$temp_request = new WP_REST_Request( 'POST' );
			$temp_request->set_body_params(
				[
					'job_id' => $next_jobid,
				]
				);

			$return = $this->start_backup( $temp_request );
		} catch ( Exception $e ) {
			$return['error'] = $e->getMessage();
		}
		return rest_ensure_response( $return );
	}
}
