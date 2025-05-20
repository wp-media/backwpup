<?php

namespace WPMedia\BackWPup\Frontend\API;

use WPMedia\BackWPup\API\Rest as RestInterface;
use WPMedia\BackWPup\Adapters\BackWPupHelpersAdapter;

class Rest implements RestInterface {

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
	 * Instance of BackWPupHelpersAdapter.
	 *
	 * @var BackWPupHelpersAdapter
	 */
	private BackWPupHelpersAdapter $helpers_adapter;

	/**
	 * Constructor for the Rest class.
	 *
	 * Initializes the Rest API handler with the provided helpers adapter.
	 *
	 * @param BackWPupHelpersAdapter $helpers_adapter Adapter providing helper functions for BackWPup.
	 */
	public function __construct(
		BackWPupHelpersAdapter $helpers_adapter
	) {
		$this->helpers_adapter = $helpers_adapter;
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
			self::ROUTE_NAMESPACE,
			'/getblock',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'getblock' ],
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
	 * Get the block content.
	 *
	 * This function retrieves the HTML content for a specified block based on the provided parameters.
	 * It checks for the presence of 'block_name' and 'block_type' in the request parameters and
	 * calls the appropriate method to get the block content.
	 *
	 * @param \WP_REST_Request $request The REST request object containing the parameters.
	 *
	 * @return \WP_HTTP_Response The response object containing the HTML content and status.
	 *
	 * @throws \Exception If an error occurs during the process.
	 */
	public function getblock( \WP_REST_Request $request ) {
		$params = $request->get_params();
		$html   = '';
		$status = 200;
		try {
			if ( ! isset( $params['block_name'] ) ) {
				throw new \Exception( __( 'No block name set.', 'backwpup' ) );
			}
			if ( ! isset( $params['block_type'] ) || ! in_array( $params['block_type'], self::$authorised_blocks_type, true ) ) {
				throw new \Exception( __( 'Wrong block type set.', 'backwpup' ) );
			}
			$method = $params['block_type'];
			$data   = $params['block_data'] ?? [];
			if ( 'component' === $method ) {
				$html = $this->helpers_adapter->$method( $params['block_name'], $data, true );
			} else {
				$html = $this->helpers_adapter->$method( $params['block_name'], true, $data );
			}
		} catch ( \Exception $e ) {
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
		return rest_ensure_response( new \WP_REST_Response( $html, $status ) );
	}
}
