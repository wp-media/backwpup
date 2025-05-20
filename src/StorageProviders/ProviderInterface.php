<?php
namespace WPMedia\BackWPup\StorageProviders;

use WP_REST_Request;

interface ProviderInterface {
	/**
	 * Retrieves the name of the storage provider.
	 *
	 * @return string The name of the storage provider.
	 */
	public function get_name(): string;

	/**
	 * Checks if the storage provider is authenticated for the given job.
	 *
	 * @param int $job_id The ID of the job to check authentication for.
	 * @return string|null Returns a string with an authentication message if not authenticated, or null if authenticated.
	 */
	public function is_authenticated( $job_id ): ?string;

	/**
	 * Deletes the authentication information associated with the given REST request.
	 *
	 * @param WP_REST_Request $request The REST request containing authentication data to be deleted.
	 * @return string|null Returns a string on success, or null if deletion fails or is not applicable.
	 */
	public function delete_auth( WP_REST_Request $request ): ?string;

	/**
	 * Authenticates a request and returns an authentication token or identifier.
	 *
	 * @param WP_REST_Request $request The REST request object containing authentication details.
	 * @return string|null The authentication token or identifier if authentication is successful, or null on failure.
	 */
	public function authenticate( WP_REST_Request $request ): ?string;
}
