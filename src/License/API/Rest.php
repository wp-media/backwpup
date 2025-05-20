<?php

namespace WPMedia\BackWPup\License\API;

use WPMedia\BackWPup\API\Rest as RestInterface;
use WPMedia\BackWPup\Adapters\BackWPupAdapter;
use WP_REST_Request;
use WP_HTTP_Response;
use Exception;
use Inpsyde\BackWPup\Pro\License\LicenseSettingUpdater;

class Rest implements RestInterface {

	/**
	 * BackWPupAdapter instance.
	 *
	 * @var BackWPupAdapter
	 */
	private BackWPupAdapter $backwpup_adapter;

	/**
	 * Instance of the LicenseSettingUpdater class used to manage and update license settings.
	 *
	 * @var LicenseSettingUpdater
	 */
	private LicenseSettingUpdater $license_setting_updater;

	/**
	 * Constructor.
	 *
	 * @param BackWPupAdapter       $backwpup_adapter Adapter for handling BackWPup operations.
	 * @param LicenseSettingUpdater $license_setting_updater
	 */
	public function __construct(
		BackWPupAdapter $backwpup_adapter,
		LicenseSettingUpdater $license_setting_updater
	) {
		$this->backwpup_adapter        = $backwpup_adapter;
		$this->license_setting_updater = $license_setting_updater;
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
			'/license_update',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'license_update' ],
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
			if ( ! $this->backwpup_adapter->is_pro() ) {
				throw new Exception( __( 'This feature is only available in the Pro version.', 'backwpup' ) );
			}
			// If plugin data is needed, dependencies should be constructed with correct data in the service provider.
			$message = $this->license_setting_updater->update();

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
}
