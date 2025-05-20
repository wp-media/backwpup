<?php

namespace WPMedia\BackWPup\StorageProviders\Frontend\API;

use WPMedia\BackWPup\API\Rest as RestInterface;
use WPMedia\BackWPup\Adapters\BackWPupHelpersAdapter;
use WPMedia\BackWPup\Adapters\OptionAdapter;

use WP_HTTP_Response;
use Exception;

class Rest implements RestInterface {

	/**
	 * Instance of Job adapter
	 *
	 * @var BackWPupHelpersAdapter
	 */
	private BackWPupHelpersAdapter $helper_adapter;

	/**
	 * Instance of Option adapter
	 *
	 * @var OptionAdapter
	 */
	private OptionAdapter $option_adapter;

	/**
	 * Constructor.
	 *
	 * @param BackWPupHelpersAdapter $helper_adapter    Adapter for handling job operations.
	 * @param OptionAdapter          $option_adapter Adapter for managing options.
	 */
	public function __construct( BackWPupHelpersAdapter $helper_adapter, OptionAdapter $option_adapter ) {
		$this->helper_adapter = $helper_adapter;
		$this->option_adapter = $option_adapter;
	}

	/**
	 * Registers the REST API routes for the BackWPup plugin.
	 *
	 * This method is responsible for defining the routes that the plugin
	 * exposes via the WordPress REST API. Each route should be registered
	 * with its corresponding callback and permissions.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			'backwpup/v1',
			'/storagelistcompact',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_storage_list_compact' ],
				'permission_callback' => [ $this, 'has_permission' ],
			]
		);
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
			$storage_destination = $this->option_adapter->get( $files_job_id, 'destinations', [] );
			$html                = $this->helper_adapter->component( 'storage-list-compact', [ 'storages' => $storage_destination ] );
		}
		catch ( Exception $e ) {
			$status = 500;
			$html   = $this->helper_adapter->component(
				'alerts/info',
				[
					'type'    => 'alert',
					'font'    => 'xs',
					"content" => __($e->getMessage(), 'backwpup'), //phpcs:ignore
				]
				);
		}
		$response = new WP_HTTP_Response( $html, $status, [ 'Content-Type' => 'text/html' ] );
		return rest_ensure_response( $response );
	}
}
