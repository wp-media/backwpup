<?php
namespace WPMedia\BackWPup\StorageProviders\SugarSync;

use WP_REST_Request;
use WPMedia\BackWPup\StorageProviders\ProviderInterface;
use WPMedia\BackWPup\Adapters\OptionAdapter;
use WPMedia\BackWPup\Adapters\BackWPupHelpersAdapter;
use Exception;

class SugarSyncProvider implements ProviderInterface {

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
	 * Constructor for the SugarSyncProvider class.
	 *
	 * Initializes the SugarSyncProvider with the required option and helper adapters.
	 *
	 * @param OptionAdapter          $option_adapter   Adapter for handling options.
	 * @param BackWPupHelpersAdapter $helpers_adapter  Adapter for helper functions.
	 */
	public function __construct(
		OptionAdapter $option_adapter,
		BackWPupHelpersAdapter $helpers_adapter
	) {
		$this->option_adapter  = $option_adapter;
		$this->helpers_adapter = $helpers_adapter;
	}

	/**
	 * Returns the unique name identifier for the SugarSync storage provider.
	 *
	 * @return string The name of the storage provider ('sugarsync').
	 */
	public function get_name(): string {
		return 'sugarsync';
	}

	/**
	 * Checks if the SugarSync provider is authenticated for the given job ID.
	 *
	 * Retrieves the refresh token from the option adapter to determine authentication status.
	 * Returns an alert/info component indicating whether the provider is authenticated.
	 *
	 * @param int $job_id The job ID to check authentication for.
	 * @return string|null HTML for the authentication status alert, or null on failure.
	 */
	public function is_authenticated( $job_id ): ?string {
		$refresh_token      = $this->option_adapter->get( $job_id, 'sugarrefreshtoken' );
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
	 * Deletes the SugarSync authentication token for the specified backup job(s).
	 *
	 * This method handles a REST request to remove the SugarSync refresh token associated with a backup job.
	 * If a 'job_id' parameter is provided in the request, it deletes the token for that job.
	 * If not, it attempts to retrieve the default backup files job ID from the site options and deletes the token for that job and the next consecutive job ID.
	 * Throws an exception if no backup jobs are set.
	 *
	 * @param WP_REST_Request $request The REST request containing parameters, optionally including 'job_id'.
	 * @return string|null The rendered HTML for the SugarSync API connection sidebar part, or null on failure.
	 * @throws Exception If no backup jobs are set in the site options.
	 */
	public function delete_auth( WP_REST_Request $request ): ?string {
		$params = $request->get_params();
		if ( ! empty( $params['job_id'] ) ) {
			$jobs_ids = [ $params['job_id'] ];
		} else {
			$jobs_ids = $this->get_job_ids();
		}
		foreach ( $jobs_ids as $jobid ) {
			$this->option_adapter->delete( $jobid, 'sugarrefreshtoken' );
		}
		return $this->helpers_adapter->children( 'sidebar/sugar-sync-parts/api-connexion', true, [ 'job_id' => $jobs_ids[0] ] );
	}

	/**
	 * Authenticates a user with SugarSync using credentials provided in the REST request.
	 *
	 * Validates the presence of 'sugaremail' and 'sugarpass' parameters in the request.
	 * Determines the job IDs to update based on the presence of 'job_id' in the request or retrieves them from site options.
	 * Attempts to obtain a SugarSync refresh token using the provided credentials.
	 * If successful, updates the refresh token for the relevant job IDs.
	 * Returns the rendered API connection sidebar part for the first job ID.
	 *
	 * @param WP_REST_Request $request The REST request containing authentication parameters.
	 * @return string|null Rendered HTML for the API connection sidebar part, or null on failure.
	 * @throws Exception If required parameters are missing or no backup jobs are set.
	 */
	public function authenticate( WP_REST_Request $request ): ?string {
		$params = $request->get_params();
		if ( ! isset( $params['sugaremail'] ) || '' === $params['sugaremail'] ) {
			throw new Exception( __( 'No email set.', 'backwpup' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}
		if ( ! isset( $params['sugarpass'] ) || '' === $params['sugarpass'] ) {
			throw new Exception( __( 'No password set.', 'backwpup' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}
		if ( ! empty( $params['job_id'] ) ) {
			$jobs_ids = [ $params['job_id'] ];
		} else {
			$jobs_ids = $this->get_job_ids();
		}
		$sugarsync     = new \BackWPup_Destination_SugarSync_API();
		$refresh_token = $sugarsync->get_Refresh_Token( sanitize_email( $params['sugaremail'] ), $params['sugarpass'] );
		if ( ! empty( $refresh_token ) ) {
			foreach ( $jobs_ids as $jobid ) {
				$this->option_adapter->update( (int) $jobid, 'sugarrefreshtoken', $refresh_token );
			}
		}
		return $this->helpers_adapter->children( 'sidebar/sugar-sync-parts/api-connexion', true, [ 'job_id' => $jobs_ids[0] ] );
	}

	/**
	 * Return jobs id
	 *
	 * @return array
	 * @throws Exception Throw exception when $files_job_id is missing.
	 */
	private function get_job_ids(): array {
		$files_job_id    = get_site_option( 'backwpup_backup_files_job_id', false );
		$database_job_id = get_site_option( 'backwpup_backup_database_job_id', false );
		$first_job_id    = get_site_option( 'backwpup_first_backup_job_id', false );

		if ( false === $files_job_id ) {
			throw new Exception( __( 'No backup jobs set.', 'backwpup' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		return [ $files_job_id, $database_job_id, $first_job_id ];
	}
}
