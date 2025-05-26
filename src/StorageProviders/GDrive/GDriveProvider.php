<?php
namespace WPMedia\BackWPup\StorageProviders\GDrive;

use WP_REST_Request;
use WPMedia\BackWPup\StorageProviders\ProviderInterface;
use WPMedia\BackWPup\Adapters\OptionAdapter;
use WPMedia\BackWPup\Adapters\BackWPupHelpersAdapter;
use WPMedia\BackWPup\Adapters\EncryptionAdapter;

class GDriveProvider implements ProviderInterface {

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
	 * Instance of EncryptionAdapter.
	 *
	 * @var EncryptionAdapter
	 */
	private $encryption_adapter;

	/**
	 * GDriveProvider constructor.
	 *
	 * Initializes the Google Drive storage provider with the required adapters.
	 *
	 * @param OptionAdapter          $option_adapter     Adapter for handling plugin options.
	 * @param BackWPupHelpersAdapter $helpers_adapter    Adapter for helper functions.
	 * @param EncryptionAdapter      $encryption_adapter Adapter for encryption operations.
	 */
	public function __construct( OptionAdapter $option_adapter, BackWPupHelpersAdapter $helpers_adapter, EncryptionAdapter $encryption_adapter ) {
		$this->option_adapter     = $option_adapter;
		$this->helpers_adapter    = $helpers_adapter;
		$this->encryption_adapter = $encryption_adapter;
	}

	/**
	 * Returns the unique name identifier for the Google Drive storage provider.
	 *
	 * @return string The storage provider name, 'gdrive'.
	 */
	public function get_name(): string {
		return 'gdrive';
	}

	/**
	 * Checks if the Google Drive provider is authenticated for the given job.
	 *
	 * Decrypts and retrieves the refresh token for the specified job ID. Returns an alert component
	 * indicating whether authentication is successful or not.
	 *
	 * @param int|string $job_id The job ID to check authentication for.
	 * @return string|null HTML for the authentication status alert component, or null on failure.
	 */
	public function is_authenticated( $job_id ): ?string {
		$refresh_token      = $this->encryption_adapter->decrypt( (string) $this->option_adapter->get( $job_id, 'gdriverefreshtoken' ) );
		$authenticate_label = __( 'Authenticated!', 'backwpup' );
		$type               = 'info';
		if ( empty( $refresh_token ) ) {
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
	 * Deletes the authentication for the Google Drive provider.
	 *
	 * This method is intended to handle the deletion of authentication credentials
	 * for the Google Drive storage provider via a REST API request.
	 *
	 * @param WP_REST_Request $request The REST API request object.
	 * @return string|null Returns null as the default behavior.
	 */
	public function delete_auth( WP_REST_Request $request ): ?string {
		return null;
	}

	/**
	 * Authenticates a request to the Google Drive provider.
	 *
	 * This method is intended to handle authentication logic for the Google Drive storage provider.
	 * Currently, it returns null, indicating that authentication is not implemented or not required.
	 *
	 * @param WP_REST_Request $request The REST request object containing authentication details.
	 * @return string|null Returns a string on successful authentication, or null if authentication fails or is not implemented.
	 */
	public function authenticate( WP_REST_Request $request ): ?string {
		return null;
	}
}
