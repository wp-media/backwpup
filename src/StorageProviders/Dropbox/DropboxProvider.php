<?php
namespace WPMedia\BackWPup\StorageProviders\Dropbox;

use WP_REST_Request;
use WPMedia\BackWPup\StorageProviders\ProviderInterface;
use WPMedia\BackWPup\Adapters\OptionAdapter;
use WPMedia\BackWPup\Adapters\BackWPupHelpersAdapter;

class DropboxProvider implements ProviderInterface {

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
	 * DropboxProvider constructor.
	 *
	 * @param OptionAdapter          $option_adapter Instance of OptionAdapter.
	 * @param BackWPupHelpersAdapter $helpers_adapter Instance of BackWPupHelpersAdapter.
	 */
	public function __construct(
		OptionAdapter $option_adapter,
		BackWPupHelpersAdapter $helpers_adapter
	) {
		$this->option_adapter  = $option_adapter;
		$this->helpers_adapter = $helpers_adapter;
	}

	/**
	 * Returns the name identifier for the Dropbox storage provider.
	 *
	 * @return string The name of the storage provider ('dropbox').
	 */
	public function get_name(): string {
		return 'dropbox';
	}

	/**
	 * Checks if the Dropbox provider is authenticated for a given job.
	 *
	 * Retrieves the Dropbox token for the specified job ID and determines
	 * whether authentication has been completed. Returns an alert component
	 * indicating the authentication status.
	 *
	 * @param int $job_id The ID of the job to check authentication for.
	 * @return string|null The rendered alert component indicating authentication status, or null on failure.
	 */
	public function is_authenticated( $job_id ): ?string {
		$dropboxtoken       = $this->option_adapter->get( $job_id, 'dropboxtoken', [] );
		$authenticate_label = __( 'Authenticated!', 'backwpup' );
		$type               = 'info';
		if ( empty( $dropboxtoken['refresh_token'] ) ) {
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
	 * Deletes the Dropbox authentication token for the specified backup job(s).
	 *
	 * This method handles the removal of the Dropbox token associated with a backup job.
	 * If a 'job_id' parameter is provided in the REST request, it deletes the token for that job.
	 * Otherwise, it attempts to retrieve the default backup files job ID from the site options,
	 * and deletes the token for that job and the subsequent job ID.
	 *
	 * @param WP_REST_Request $request The REST request containing parameters, optionally including 'job_id'.
	 * @return string|null The rendered HTML for the Dropbox API connection sidebar part, or null on failure.
	 * @throws \Exception If no backup jobs are set in the site options.
	 */
	public function delete_auth( WP_REST_Request $request ): ?string {
		$params = $request->get_params();
		if ( isset( $params['job_id'] ) ) {
			$jobids = [ $params['job_id'] ];
		} else {
			$files_job_id = get_site_option( 'backwpup_backup_files_job_id', false );
			if ( false === $files_job_id ) {
				throw new \Exception( __( 'No backup jobs set.', 'backwpup' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			}
			$jobids = [ $files_job_id, $files_job_id + 1 ];
		}
		foreach ( $jobids as $jobid ) {
			$this->option_adapter->delete( $jobid, 'dropboxtoken' );
		}
		return $this->helpers_adapter->children( 'sidebar/dropbox-parts/api-connexion', true, [ 'job_id' => $jobids[0] ] );
	}

	/**
	 * Authenticates a request to the Dropbox storage provider.
	 *
	 * This method is intended to handle authentication logic for Dropbox integration.
	 * Currently, it returns null, indicating that authentication is not implemented or not required.
	 *
	 * @param WP_REST_Request $request The REST request object containing authentication parameters.
	 * @return string|null Returns a string on successful authentication, or null if authentication fails or is not implemented.
	 */
	public function authenticate( WP_REST_Request $request ): ?string {
		return null;
	}
}
