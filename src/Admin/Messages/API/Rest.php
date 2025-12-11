<?php

namespace WPMedia\BackWPup\Admin\Messages\API;

use WP_HTTP_Response;
use WP_REST_Request;
use WPMedia\BackWPup\API\Rest as RestInterface;


class Rest implements RestInterface {

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
			self::ROUTE_V2_NAMESPACE,
			'/messages',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_messages' ],
				'permission_callback' => [ $this, 'has_permission' ],
				'args'                => [],
			]
		);
	}


	/**
	 * Retrieves stored messages from the site options.
	 *
	 * This method fetches the messages stored in the 'backwpup_messages' site option
	 * and returns them wrapped in an HTTP response.
	 *
	 * @param WP_REST_Request $request The REST API request object.
	 *
	 * @return WP_HTTP_Response The response containing the messages and HTTP status code 200.
	 */
	public function get_messages( WP_REST_Request $request ) {
		$messages = get_site_option( 'backwpup_messages', [] );
		// delete it after messages send.
		update_site_option( 'backwpup_messages', [] );
		return new WP_HTTP_Response( $messages, 200 );
	}
}
