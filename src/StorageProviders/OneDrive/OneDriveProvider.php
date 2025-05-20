<?php
namespace WPMedia\BackWPup\StorageProviders\OneDrive;

use WP_REST_Request;
use WPMedia\BackWPup\StorageProviders\ProviderInterface;
use WPMedia\BackWPup\Adapters\OptionAdapter;
use WPMedia\BackWPup\Adapters\BackWPupHelpersAdapter;

class OneDriveProvider implements ProviderInterface {
	/**
	 * Instance of OptionAdapter.
	 *
	 * @var OptionAdapter
	 */
	private $option_adapter;

	/**
	 * Instance of BackWPupHelpersAdapter.
	 *
	 * @var BackWPupHelpersAdapter
	 */
	private $helpers_adapter;

	/**
	 * OneDriveProvider constructor.
	 *
	 * Initializes the OneDriveProvider with the given option and helpers adapters.
	 *
	 * @param OptionAdapter          $option_adapter   Adapter for handling option settings.
	 * @param BackWPupHelpersAdapter $helpers_adapter   Adapter for BackWPup helper functions.
	 */
	public function __construct(
		OptionAdapter $option_adapter,
		BackWPupHelpersAdapter $helpers_adapter
	) {
		$this->option_adapter  = $option_adapter;
		$this->helpers_adapter = $helpers_adapter;
	}

	/**
	 * Returns the unique name identifier for the OneDrive storage provider.
	 *
	 * @return string The name of the storage provider ('onedrive').
	 */
	public function get_name(): string {
		return 'onedrive';
	}

	/**
	 * Checks if the OneDrive provider is authenticated for the given job ID.
	 *
	 * Retrieves the client state from the option adapter and determines if an access token is present.
	 * Returns an alert/info component indicating the authentication status.
	 *
	 * @param int $job_id The job ID to check authentication for.
	 * @return string|null HTML string for the authentication status alert, or null on failure.
	 */
	public function is_authenticated( $job_id ): ?string {
		$client_state       = $this->option_adapter->get( $job_id, 'onedrive_client_state' );
		$authenticate_label = __( 'Authenticated!', 'backwpup' );
		$type               = 'info';
		if ( ! isset( $client_state->token->data->access_token ) ) {
			$authenticate_label = __( 'Not authenticated!', 'backwpup' );
			$type               = 'alert';
		}
		return $this->helpers_adapter->component(
			'alerts/info',
			[
				'type'    => $type,
				'font'    => 's',
				'content' => $authenticate_label,
			]
		);
	}

	/**
	 * Deletes the authentication for the OneDrive provider.
	 *
	 * Handles the REST request to remove stored authentication credentials.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 * @return string|null Returns a string on success or null on failure.
	 */
	public function delete_auth( WP_REST_Request $request ): ?string {
		return null;
	}

	/**
	 * Handles the authentication process for the OneDrive storage provider.
	 *
	 * This method is intended to authenticate a request made via the WordPress REST API.
	 * It currently returns null, indicating that authentication is not implemented or not required.
	 *
	 * @param WP_REST_Request $request The REST API request object.
	 * @return string|null Returns a string on successful authentication, or null if authentication fails or is not implemented.
	 */
	public function authenticate( WP_REST_Request $request ): ?string {
		return null;
	}
}
