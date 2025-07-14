<?php

namespace WPMedia\BackWPup\Jobs\API;

use WP_REST_Server;
use WPMedia\BackWPup\Adapters\JobAdapter;
use WPMedia\BackWPup\Adapters\OptionAdapter;
use WPMedia\BackWPup\Adapters\CronAdapter;
use WPMedia\BackWPup\Adapters\JobTypesAdapter;
use WPMedia\BackWPup\Adapters\BackWPupAdapter;
use WPMedia\BackWPup\Adapters\EncryptionAdapter;
use WPMedia\BackWPup\Plugin\Plugin;
use Exception;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use WPMedia\BackWPup\API\Rest as RestInterface;

/**
 * REST API handler for BackWPup jobs.
 */
class Rest implements RestInterface {

	/**
	 * Job adapter instance.
	 *
	 * @var JobAdapter
	 */
	private $job_adapter;
	/**
	 * Option adapter instance.
	 *
	 * @var OptionAdapter
	 */
	private $option_adapter;
	/**
	 * Cron adapter instance.
	 *
	 * @var CronAdapter
	 */
	private $cron_adapter;
	/**
	 * Job types adapter instance.
	 *
	 * @var JobTypesAdapter
	 */
	private $job_types_adapter;

	/**
	 * Instance of BackWPupAdapter.
	 *
	 * @var BackWPupAdapter
	 */
	private $backwpup_adapter;

	/**
	 * Instance of EncryptionAdapter
	 *
	 * @var EncryptionAdapter
	 */
	private EncryptionAdapter $encryption_adapter;

	/**
	 * Constructor.
	 *
	 * @param JobAdapter        $job_adapter      Job adapter instance.
	 * @param OptionAdapter     $option_adapter   Option adapter instance.
	 * @param CronAdapter       $cron_adapter     Cron adapter instance.
	 * @param JobTypesAdapter   $job_types_adapter Job types adapter instance.
	 * @param BackWPupAdapter   $backwpup_adapter BackWPup adapter instance.
	 * @param EncryptionAdapter $encryption_adapter Encryption adapter instance.
	 */
	public function __construct( JobAdapter $job_adapter, OptionAdapter $option_adapter, CronAdapter $cron_adapter, JobTypesAdapter $job_types_adapter, BackWPupAdapter $backwpup_adapter, EncryptionAdapter $encryption_adapter ) {
		$this->job_adapter        = $job_adapter;
		$this->option_adapter     = $option_adapter;
		$this->cron_adapter       = $cron_adapter;
		$this->job_types_adapter  = $job_types_adapter;
		$this->backwpup_adapter   = $backwpup_adapter;
		$this->encryption_adapter = $encryption_adapter;
	}

	/**
	 * Check if the user has permission to access the route.
	 *
	 * @return bool
	 */
	public function has_permission(): bool {
		return current_user_can( 'backwpup' );
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			self::ROUTE_NAMESPACE,
			'/updatejob',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'update_job' ],
				'permission_callback' => [ $this, 'has_permission' ],
				'args'                => [
					'job_id'               => [
						'required'          => true,
						'validate_callback' => function ( $param ) {
							return ( ( is_numeric( $param ) && $param > 0 ) || '' === $param );
						},
						'sanitize_callback' => 'absint',
					],
					'activ'                => [
						'validate_callback' => function ( $param ) {
							return is_bool( filter_var( $param, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE ) );
						},
						'sanitize_callback' => function ( $param ) {
							return filter_var( $param, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
						},
					],
					'storage_destinations' => [
						'validate_callback' => function ( $param ) {
							return is_array( $param ) || is_null( $param );
						},
						'sanitize_callback' => function ( $param ) {
							return is_array( $param ) ? array_filter( $param ) : [];
						},
					],
				],
			]
		);

		register_rest_route(
			self::ROUTE_NAMESPACE,
			'/update-job-title',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'update_job_title' ],
				'permission_callback' => [ $this, 'has_permission' ],
				'args'                => [
					'job_id' => [
						'required'          => true,
						'validate_callback' => function ( $param ) {
							return is_numeric( $param ) && $param > 0;
						},
						'sanitize_callback' => 'absint',
					],
					'title'  => [
						'required'          => true,
						'validate_callback' => function ( $param ) {
							return is_string( $param ) && ! empty( trim( $param ) );
						},
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);

		register_rest_route(
			self::ROUTE_NAMESPACE,
			'/addjob',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'add_job' ],
				'permission_callback' => [ $this, 'has_permission' ],
				'args'                => [
					'type' => [
						'required'          => true,
						'validate_callback' => function ( $param ) {
							return in_array( $param, [ 'files', 'database', 'mixed' ], true );
						},
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);

		register_rest_route(
			self::ROUTE_NAMESPACE,
			'/delete_job',
			[
				'methods'             => 'DELETE',
				'callback'            => [ $this, 'delete_job' ],
				'permission_callback' => [ $this, 'has_permission' ],
				'args'                => [
					'job_id' => [
						'required'          => true,
						'validate_callback' => function ( $param ) {
							return is_numeric( $param ) && $param > 0;
						},
						'sanitize_callback' => 'absint',
					],
				],
			]
		);

		register_rest_route(
			self::ROUTE_NAMESPACE,
			'/save_job_settings',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'save_job_settings' ],
				'permission_callback' => [ $this, 'has_permission' ],
			]
		);

		register_rest_route(
			self::ROUTE_NAMESPACE,
			'/save_files_exclusions',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'save_files_exclusions' ],
				'permission_callback' => [ $this, 'has_permission' ],
				'args'                => [
					'job_id'                   => [
						'required'          => true,
						'validate_callback' => function ( $param ) {
							return is_numeric( $param ) && $param > 0;
						},
						'sanitize_callback' => 'absint',
					],
					'backuproot'               => [
						'validate_callback' => function ( $param ) {
							return is_scalar( $param ) || is_null( $param ); },
						'sanitize_callback' => 'absint',
					],
					'backupplugins'            => [
						'validate_callback' => function ( $param ) {
							return is_scalar( $param ) || is_null( $param ); },
						'sanitize_callback' => 'absint',
					],
					'backupthemes'             => [
						'validate_callback' => function ( $param ) {
							return is_scalar( $param ) || is_null( $param );
						},
						'sanitize_callback' => 'absint',
					],
					'backupuploads'            => [
						'validate_callback' => function ( $param ) {
							return is_scalar( $param ) || is_null( $param ); },
						'sanitize_callback' => 'absint',
					],
					'backupcontent'            => [
						'validate_callback' => function ( $param ) {
							return is_scalar( $param ) || is_null( $param ); },
						'sanitize_callback' => 'absint',
					],
					'fileexclude'              => [
						'validate_callback' => function ( $param ) {
							return is_string( $param ) || is_null( $param ); },
						'sanitize_callback' => 'sanitize_text_field',
					],
					'add'                      => [
						'validate_callback' => function ( $param ) {
							return is_string( $param ) || is_null( $param ); },
						'sanitize_callback' => 'sanitize_text_field',
					],
					'backuppluginsexcludedirs' => [
						'validate_callback' => function ( $param ) {
							return is_array( $param ) || is_null( $param ); },
						'sanitize_callback' => function ( $param ) {
							return is_array( $param ) ? array_map( 'sanitize_text_field', $param ) : []; },
					],
					'backupthemesexcludedirs'  => [
						'validate_callback' => function ( $param ) {
							return is_array( $param ) || is_null( $param ); },
						'sanitize_callback' => function ( $param ) {
							return is_array( $param ) ? array_map( 'sanitize_text_field', $param ) : []; },
					],
					'backupcontentexcludedirs' => [
						'validate_callback' => function ( $param ) {
							return is_array( $param ) || is_null( $param ); },
						'sanitize_callback' => function ( $param ) {
							return is_array( $param ) ? array_map( 'sanitize_text_field', $param ) : []; },
					],
				],
			]
		);

		register_rest_route(
			self::ROUTE_NAMESPACE,
			'/save_excluded_tables',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'save_excluded_tables' ],
				'permission_callback' => [ $this, 'has_permission' ],
				'args'                => [
					'job_id'                => [
						'required'          => true,
						'validate_callback' => function ( $param ) {
							return is_numeric( $param ) && $param > 0;
						},
						'sanitize_callback' => 'absint',
					],
					'tabledb'               => [
						'validate_callback' => function ( $param ) {
							return is_array( $param ) || is_null( $param );
						},
						'sanitize_callback' => function ( $param ) {
							return is_array( $param ) ? array_map( 'sanitize_text_field', $param ) : [];
						},
					],
					'dbdumpfile'            => [
						'validate_callback' => function ( $param ) {
							return is_string( $param ) || is_null( $param ); },
						'sanitize_callback' => 'sanitize_text_field',
					],
					'dbdumpwpdbsettings'    => [
						'validate_callback' => function ( $param ) {
							return is_scalar( $param ) || is_null( $param ); },
						'sanitize_callback' => 'absint',
					],
					'dbdumpfilecompression' => [
						'validate_callback' => function ( $param ) {
							return is_string( $param ) || is_null( $param ); },
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);

		register_rest_route(
			self::ROUTE_NAMESPACE,
			'/save_site_option',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'save_site_option' ],
				'permission_callback' => [ $this, 'has_permission' ],
			]
		);

		/**
		 * Edit job endpoint.
		*/
		register_rest_route(
			self::ROUTE_V2_NAMESPACE,
			'/backups/(?P<id>\d+)/type',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'update_backup_type' ],
				'permission_callback' => [ $this, 'has_permission' ],
				'args'                => [
					'id'   => [
						'required'          => true,
						'validate_callback' => function ( $param ) {
							return is_numeric( $param ) && $param > 0;
						},
						'sanitize_callback' => 'absint',
					],
					'type' => [
						'required'          => true,
						'validate_callback' => function ( $param ) {
							return in_array( $param, [ 'files', 'database', 'mixed' ], true );
						},
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);
	}

	/**
	 * Update job.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response
	 * @throws Exception If the job title update fails.
	 */
	public function update_job( WP_REST_Request $request ): WP_REST_Response {
		$params = $request->get_params();
		$return = [];
		$status = 200;

		try {
			if ( isset( $params['job_id'] ) && isset( $params['activ'] ) ) {
				$job_id = $params['job_id'];
				$activ  = $params['activ'];

				if ( ! $activ ) {
					$this->job_adapter->disable_job( $job_id );
					$next_backup_label = __( 'No backup scheduled', 'backwpup' );
				} else {
					$this->job_adapter->enable_job( $job_id );
					$this->job_adapter->schedule_job( $job_id );
					$cron_next         = $this->cron_adapter->cron_next( $this->option_adapter->get( $job_id, 'cron' ) );
					$next_backup_label = sprintf(
						__( '%1$s at %2$s', 'backwpup' ), // phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
						wp_date( get_option( 'date_format' ), $cron_next ),
						wp_date( get_option( 'time_format' ), $cron_next )
					);
				}

				$return['message'] = $next_backup_label;
			} else {
				if ( isset( $params['job_id'] ) && 0 !== $params['job_id'] && isset( $params['storage_destinations'] ) ) {
					$jobs = [ $params['job_id'] ];
				} else {
					$first_job_id = get_site_option( Plugin::FIRST_JOB_ID, false );
					if ( false === $first_job_id ) {
						throw new Exception( __( 'First job not found', 'backwpup' ) );
					}
					$jobs = [ $first_job_id ];
				}
				foreach ( $jobs as $a_job ) {
					$this->option_adapter->update( $a_job, 'destinations', $params['storage_destinations'] );
				}
				$return['message'] = __( 'Backup updated.', 'backwpup' );
			}
		} catch ( Exception $e ) {
			$status          = 500;
			$return['error'] = $e->getMessage();
		}

		return rest_ensure_response( $return );
	}

	/**
	 * Update backup
	 *
	 * @param WP_REST_Request $request Request parameters.
	 *
	 * @return WP_REST_Response
	 */
	public function update_backup_type( WP_REST_Request $request ): WP_REST_Response {
		$params = $request->get_params();
		$return = [
			'status' => 200,
		];

		try {
			$job_id = $params['id'];

			$job_data = [
				'type' => $this->job_types_adapter->job_type_map( $params['type'] ),
				'name' => $this->job_types_adapter->job_name_map( $params['type'] ),
			];

			foreach ( $job_data as $key => $value ) {
				$this->option_adapter->update( $job_id, $key, $value );
			}
		} catch ( Exception $e ) {
			$return['status'] = 500;
			$return['error']  = $e->getMessage();
		}

		return rest_ensure_response( $return );
	}

	/**
	 * Update job title.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response
	 * @throws Exception If the job title update fails.
	 */
	public function update_job_title( WP_REST_Request $request ): WP_REST_Response {
		$params = $request->get_params();
		$title  = $params['title'];

		$this->job_adapter->rename_job( $params['job_id'], $title );

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
	 * Add job.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response
	 */
	public function add_job( WP_REST_Request $request ): WP_REST_Response {
		$params = $request->get_params();

		$default_values = $this->option_adapter->defaults_job();

		$type   = $params['type'];
		$job_id = $this->option_adapter->next_job_id();

		$job = [
			'activ' => true,
			'jobid' => $job_id,
		];

		switch ( $type ) {
			case 'files':
				$job['type'] = $this->job_types_adapter->get_type_job_file();
				$job['name'] = $this->job_types_adapter->get_name_job_files();
				break;
			case 'database':
				$job['type'] = $this->job_types_adapter->get_type_job_database();
				$job['name'] = $this->job_types_adapter->get_name_job_database();
				break;
			case 'mixed':
				$job['type'] = $this->job_types_adapter->get_type_job_both();
				$job['name'] = $this->job_types_adapter->get_name_job_both();
				break;
		}

		$job = wp_parse_args( $job, $default_values );

		foreach ( $job as $key => $value ) {
			$this->option_adapter->update( $job_id, $key, $value );
		}

		$cron_next = $this->cron_adapter->cron_next( $job['cron'] );

		wp_schedule_single_event( $cron_next, 'backwpup_cron', [ 'arg' => $job_id ] );

		return rest_ensure_response(
			[
				'success' => true,
				'message' => __( 'You scheduled a new backup successfully!<br>Now you can configure it as you wish.', 'backwpup' ),
			]
			);
	}

	/**
	 * Delete job.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response
	 */
	public function delete_job( WP_REST_Request $request ) {
		$params = $request->get_params();
		$return = [
			'success' => true,
			'message' => __( 'Job deleted successfully.', 'backwpup' ),
		];
		$status = 200;

		$job_id = $params['job_id'];
		if ( ! $this->job_adapter->delete_job( $job_id ) ) {
			$return['success'] = false;
			$return['message'] = __( 'Failed to delete job', 'backwpup' );
		}

		$response = rest_ensure_response( $return );
		$response->set_status( $status );
		return $response;
	}

	/**
	 * Save the job frequency settings via the REST API.
	 *
	 * This function updates the cron schedule for a given job and reschedules it accordingly.
	 * It generates a new cron expression based on user input and stores it in the database.
	 *
	 * @param WP_REST_Request $request The REST API request containing job settings.
	 *
	 * @return WP_REST_Response|WP_Error|WP_HTTP_Response The response containing the updated job schedule or an error message.
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
			$new_cron_expression = $this->cron_adapter->get_basic_cron_expression(
				$frequency,
				$start_time[0],
				$start_time[1],
				$day_of_week,
				$day_of_month
			);

			// Update the cron expression in the job settings.
			$this->option_adapter->update( $job_id, 'cron', $new_cron_expression );

			// Re-schedule the job with the updated cron schedule.
			$this->job_adapter->schedule_job( $job_id );

			// Get the next scheduled execution time.
			$cron_next = $this->cron_adapter->cron_next( $new_cron_expression );

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
	 * Save files exclusions via REST API.
	 *
	 * This method handles the saving of file exclusions based on the provided
	 * parameters from the WP_REST_Request. It updates the file exclusions for the job.
	 *
	 * @param WP_REST_Request $request The REST request object containing the parameters.
	 *
	 * @return WP_REST_Response The response object containing the status and message.
	 *
	 * @throws Exception If an error occurs during the process.
	 */
	public function save_files_exclusions( WP_REST_Request $request ) {
		$params = $request->get_params();
		$return = [];

		$job_types = $this->backwpup_adapter->get_job_types();
		if ( isset( $job_types['FILE'] ) ) {
			$job_types['FILE']->edit_form_post_save( $params['job_id'], $params );
		}
		$return['status']  = 200;
		$return['message'] = __( 'File exclusions saved successfully.', 'backwpup' );

		return rest_ensure_response( $return );
	}

		/**
		 * Save excluded tables via REST API.
		 *
		 * This method handles the saving of excluded database tables based on the provided
		 * parameters from the WP_REST_Request. It updates the excluded tables for the given job
		 *
		 * @param WP_REST_Request $request The REST request object containing the parameters.
		 *
		 * @return WP_REST_Response The response object containing the status and message.
		 *
		 * @throws Exception If an error occurs during the process.
		 */
	public function save_excluded_tables( WP_REST_Request $request ): WP_REST_Response {
		$params = $request->get_params();
		$return = [];
		$job_id = $params['job_id'];

		$job_types = $this->backwpup_adapter->get_job_types();
		if ( isset( $job_types['DBDUMP'] ) ) {
			$job_types['DBDUMP']->edit_form_post_save( $job_id, $params );
		}

		$return['status']  = 200;
		$return['message'] = __( 'Excluded tables saved successfully.', 'backwpup' );

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
	 * @return WP_REST_Response The response object containing the status and message.
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
						$value = $this->encryption_adapter->encrypt( sanitize_text_field( $value ) );
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
		$response = rest_ensure_response( $return );
		$response->set_status( $status );
		return $response;
	}
}
