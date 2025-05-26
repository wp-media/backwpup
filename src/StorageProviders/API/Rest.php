<?php
namespace WPMedia\BackWPup\StorageProviders\API;

use WP_Http;
use WP_REST_Request;
use WP_HTTP_Response;
use WP_REST_Server;
use WPMedia\BackWPup\StorageProviders\CloudProviderManager;
use WPMedia\BackWPup\API\Rest as RestInterface;
use Exception;
use WPMedia\BackWPup\Adapters\BackWPupHelpersAdapter;
use WPMedia\BackWPup\Adapters\BackWPupAdapter;
use WPMedia\BackWPup\Adapters\OptionAdapter;

class Rest implements RestInterface {

	/**
	 * Instance of BackWPupHelpersAdapter.
	 *
	 * @var BackWPupHelpersAdapter
	 */
	private $helpers_adapter;

	/**
	 * Instance of BackWPupAdapter.
	 *
	 * @var BackWPupAdapter
	 */
	private $backwpup_adapter;

	/**
	 * Instance of OptionAdapter.
	 *
	 * @var OptionAdapter
	 */
	private $option_adapter;

	/**
	 * Instance of CloudProviderManager.
	 *
	 * @var CloudProviderManager
	 */
	private $cloud_provider_manager;

	/**
	 * Constructor for the Rest storage provider API class.
	 *
	 * Initializes the Rest API with required adapters and the cloud provider manager.
	 *
	 * @param BackWPupHelpersAdapter $helpers_adapter        Adapter for BackWPup helper functions.
	 * @param BackWPupAdapter        $backwpup_adapter       Adapter for BackWPup core functionality.
	 * @param OptionAdapter          $option_adapter         Adapter for handling plugin options.
	 * @param CloudProviderManager   $cloud_provider_manager Manager for cloud storage providers.
	 */
	public function __construct(
		BackWPupHelpersAdapter $helpers_adapter,
		BackWPupAdapter $backwpup_adapter,
		OptionAdapter $option_adapter,
		CloudProviderManager $cloud_provider_manager
	) {
		$this->helpers_adapter        = $helpers_adapter;
		$this->backwpup_adapter       = $backwpup_adapter;
		$this->option_adapter         = $option_adapter;
		$this->cloud_provider_manager = $cloud_provider_manager;
	}

	/**
	 * Registers custom REST API routes for cloud authentication and management.
	 *
	 * This method defines the following REST endpoints under the specified namespace:
	 * - GET    /cloud_is_authenticated:    Checks if the cloud is authenticated.
	 * - POST   /authenticate_cloud:        Authenticates the cloud connection.
	 * - POST   /delete_auth_cloud:         Deletes the cloud authentication.
	 * - POST   /cloudsaveandtest:          Saves cloud settings and tests the connection.
	 *
	 * All routes require permission checks via the has_permission() callback.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			self::ROUTE_NAMESPACE,
			'/cloud_is_authenticated',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'cloud_is_authenticated' ],
				'permission_callback' => [ $this, 'has_permission' ],
			]
		);

		register_rest_route(
			self::ROUTE_NAMESPACE,
			'/authenticate_cloud',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'authenticate_cloud' ],
				'permission_callback' => [ $this, 'has_permission' ],
			]
		);

		register_rest_route(
			self::ROUTE_NAMESPACE,
			'/delete_auth_cloud',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'delete_auth_cloud' ],
				'permission_callback' => [ $this, 'has_permission' ],
			]
		);

		register_rest_route(
			self::ROUTE_NAMESPACE,
			'/cloudsaveandtest',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'cloud_save_and_test' ],
				'permission_callback' => [ $this, 'has_permission' ],
			]
		);

		register_rest_route(
			self::ROUTE_V2_NAMESPACE,
			'/storages',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'update_job_storage_options' ],
				'permission_callback' => [ $this, 'has_permission' ],
			]
		);
	}

	/**
	 * Checks if the current user has the required 'backwpup' capability.
	 *
	 * @return bool True if the user has permission, false otherwise.
	 */
	public function has_permission(): bool {
		return current_user_can( 'backwpup' );
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
			$provider = $this->cloud_provider_manager->get_provider( $params['cloud_name'] );
			$html     = $provider->is_authenticated( $files_job_id );
		} catch ( Exception $e ) {
			$html   = $this->helpers_adapter->component(
				'alerts/info',
				[
					'type'    => 'alert',
					'font'    => 'xs',
					'content' => $e->getMessage(),
				]
			);
			$status = 500;
		}
		return new WP_HTTP_Response( $html, $status, [ 'Content-Type' => 'text/html' ] );
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
	 * @return \WP_REST_Response The response object containing the HTML content and status.
	 *
	 * @throws Exception If 'cloud_name' is not set or if an error occurs during the process.
	 */
	public function authenticate_cloud( WP_REST_Request $request ) {
		$params = $request->get_params();
		$html   = '';
		$status = 200;
		try {
			if ( ! isset( $params['cloud_name'] ) ) {
				throw new Exception( __( 'No cloud name set.', 'backwpup' ) );
			}
			$provider = $this->cloud_provider_manager->get_provider( $params['cloud_name'] );
			$html     = $provider->authenticate( $request );
		} catch ( Exception $e ) {
			$html   = $this->helpers_adapter->component(
				'alerts/info',
				[
					'type'    => 'alert',
					'font'    => 'xs',
					'content' => $e->getMessage(),
				]
			);
			$status = 400;
		}
		return rest_ensure_response( new \WP_REST_Response( $html, $status ) );
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
	 * @return \WP_REST_Response The response object containing the HTML content and status.
	 *
	 * @throws Exception If 'cloud_name' is not set or if an error occurs during the process.
	 */
	public function delete_auth_cloud( WP_REST_Request $request ) {
		$params = $request->get_params();
		$html   = '';
		$status = 200;
		try {
			if ( ! isset( $params['cloud_name'] ) ) {
				throw new Exception( __( 'No cloud name set.', 'backwpup' ) );
			}
			$provider = $this->cloud_provider_manager->get_provider( $params['cloud_name'] );
			$html     = $provider->delete_auth( $request );
		} catch ( Exception $e ) {
				$html = $this->helpers_adapter->component(
				'alerts/info',
				[
					'type'    => 'alert',
					'font'    => 'xs',
					'content' => $e->getMessage(),
				]
			);
			$status   = 500;
		}
		return rest_ensure_response( new \WP_REST_Response( $html, $status ) );
	}

	/**
	 * Save and test the cloud connection.
	 *
	 * @param WP_REST_Request $request
	 * @throws Exception If the cloud name is not set or the cloud is not found.
	 * @return \WP_REST_Response The response object containing the HTML content and status.
	 */
	public function cloud_save_and_test( WP_REST_Request $request ) {
		$params = $request->get_params();

		$return = [];
		$status = 200;
		try {
			if ( ! isset( $params['cloud_name'] ) || '' === $params['cloud_name'] ) {
				throw new Exception( __( 'Cloud not set', 'backwpup' ) );
			}
			$cloud = $this->backwpup_adapter->get_destination( $params['cloud_name'] );
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
			if ( $should_be_connected !== $cloud->can_run( $this->option_adapter->get_job( $jobs[0] ) ) ) {
				throw new Exception( __( 'Connection failed', 'backwpup' ) );
			}
			$return['message']   = __( 'Connection successful', 'backwpup' );
			$return['connected'] = $should_be_connected;
		}
		catch ( Exception $e ) {
			$return['error'] = $e->getMessage();
			$status          = 422;
		}
		return rest_ensure_response( new \WP_REST_Response( $return, $status ) );
	}

	/**
	 * Check if the cloud is authenticated.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @throws Exception If the Storage name is not set.
	 * @throws Exception If the job id is not set.
	 * @throws Exception If the storage not found.
	 * @throws Exception If the job has only one storage and it is the one to be removed.
	 */
	public function update_job_storage_options( WP_REST_Request $request ) {
		$params = $request->get_params();
		try {
			if ( ! isset( $params['name'] ) || '' === $params['name'] ) {
				throw new Exception( __( 'Storage not set', 'backwpup' ) );
			}
			$storage_name  = $params['name'];
			$storage_class = $this->backwpup_adapter->get_destination( strtolower( $storage_name ) );

			if ( null === $storage_class ) {
				throw new Exception( __( 'Storage not found', 'backwpup' ) );
			}

			if ( ! isset( $params['job_id'] ) || '' === $params['job_id'] ) {
				throw new Exception( __( 'Job id not set', 'backwpup' ) );
			}

			$job_id = $params['job_id'];

			$backwpup_jobs = $this->option_adapter->get_job( $job_id );

			$destinations = $backwpup_jobs['destinations'];
			if ( count( $destinations ) <= 1 ) {
				throw new Exception( __( 'At least one storage is required for a job to run', 'backwpup' ) );
			}

			// Remove the storage from destinations.
			$destinations = array_filter(
				$destinations,
				function ( $value ) use ( $storage_name ) {
					return $value !== $storage_name;
				}
			);

			$destinations = array_values( $destinations );

			$this->option_adapter->update( $job_id, 'destinations', $destinations );

			$return['message'] = __( 'Connection successful', 'backwpup' );
			$return['status']  = WP_Http::OK;

		} catch ( Exception $e ) {
			$return['status'] = WP_Http::INTERNAL_SERVER_ERROR;

			$return['error'] = $e->getMessage();
		}

		return rest_ensure_response( $return );
	}
}
