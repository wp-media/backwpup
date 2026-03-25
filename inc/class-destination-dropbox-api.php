<?php

use Inpsyde\BackWPup\Infrastructure\Http\Authentication\BasicAuthCredentials;
use Inpsyde\BackWPup\Infrastructure\Http\Client\WpHttpClient;
use Inpsyde\BackWPup\Infrastructure\Http\Message\Decorator\AuthorizationRequest;
use Inpsyde\BackWPup\Infrastructure\Http\Message\Decorator\FormRequest;
use Inpsyde\BackWPup\Infrastructure\Http\Message\Decorator\JsonRequest;
use Inpsyde\BackWPup\Infrastructure\Http\Message\RequestFactory;
use Inpsyde\BackWPup\Infrastructure\Http\Message\ResponseFactory;
use Inpsyde\BackWPup\Infrastructure\Http\Message\StreamFactory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Webmozart\Assert\Assert;

/**
 * Class for communicating with Dropbox API V2.
 */
class BackWPup_Destination_Dropbox_API {

	/**
	 * URL to Dropbox API endpoint.
	 */
	public const API_URL = 'https://api.dropboxapi.com/';

	/**
	 * URL to Dropbox content endpoint.
	 */
	public const API_CONTENT_URL = 'https://content.dropboxapi.com/';

	/**
	 * URL to Dropbox for authentication.
	 */
	public const API_WWW_URL = 'https://www.dropbox.com/';

	/**
	 * API version.
	 */
	public const API_VERSION_URL = '2/';

	/**
	 * OAuth app key.
	 *
	 * @var string
	 */
	private $oauth_app_key = '';

	/**
	 * OAuth app secret.
	 *
	 * @var string
	 */
	private $oauth_app_secret = '';

	/**
	 * OAuth token data.
	 *
	 * @var array
	 */
	private $oauth_token = [];

	/**
	 * Job object for logging.
	 *
	 * @var BackWPup_Job
	 */
	private $job_object;

	/**
	 * Callback to call when token is refreshed.
	 *
	 * @var callable
	 */
	private $listener;

	/**
	 * The user agent to use in Dropbox requests.
	 *
	 * @var string
	 */
	private $user_agent;

	/**
	 * A path to the SSL ca-bundle file to use in Dropbox requests.
	 *
	 * @var string
	 */
	private $ca_bundle;

	/**
	 * BackWPup_Destination_Dropbox_API constructor.
	 *
	 * @param string            $boxtype    Dropbox type.
	 * @param BackWPup_Job|null $job_object Job object for logging.
	 *
	 * @throws BackWPup_Destination_Dropbox_API_Exception When credentials are missing.
	 */
	public function __construct( $boxtype = 'dropbox', ?BackWPup_Job $job_object = null ) {
		if ( 'dropbox' === $boxtype ) {
			$this->oauth_app_key    = get_site_option(
				'backwpup_cfg_dropboxappkey',
				'5wmuytrnjg0yhxp'
			);
			$this->oauth_app_secret = BackWPup_Encryption::decrypt(
				get_site_option( 'backwpup_cfg_dropboxappsecret', 'qv3fjv7b1pmkmlr' )
			);
		} else {
			$this->oauth_app_key    = get_site_option(
				'backwpup_cfg_dropboxsandboxappkey',
				'ktjy2puqpeeruov'
			);
			$this->oauth_app_secret = BackWPup_Encryption::decrypt(
				get_site_option( 'backwpup_cfg_dropboxsandboxappsecret', 'irux1wbof0s9xjz' )
			);
		}

		if ( empty( $this->oauth_app_key ) || empty( $this->oauth_app_secret ) ) {
			throw new BackWPup_Destination_Dropbox_API_Exception( esc_html__( 'No App key or App Secret specified.', 'backwpup' ) );
		}

		$this->job_object = $job_object;
	}

	/**
	 * List a folder.
	 *
	 * This is a functions method to use files_list_folder and
	 * files_list_folder_continue to construct an array of files within a given
	 * folder path.
	 *
	 * @param string $path Folder path.
	 *
	 * @return array
	 */
	public function list_folder( $path ) {
		$files  = [];
		$result = $this->files_list_folder( [ 'path' => $path ] );

		if ( ! $result ) {
			return [];
		}

		$files = array_merge( $files, $result['entries'] );

		$args = [ 'cursor' => $result['cursor'] ];

		while ( true === $result['has_more'] ) {
			$result         = $this->files_list_folder_continue( $args );
			$files          = array_merge( $files, $result['entries'] );
			$args['cursor'] = $result['cursor'];
		}

		return $files;
	}

	/**
	 * Uploads a file to Dropbox.
	 *
	 * @param string $file      File path.
	 * @param string $path      Destination path.
	 * @param bool   $overwrite Whether to overwrite existing file.
	 *
	 * @return array
	 * @throws BackWPup_Destination_Dropbox_API_Exception When upload fails.
	 */
	public function upload( string $file, string $path = '', bool $overwrite = true ) {
		$file = str_replace( '\\', '/', $file );

		if ( ! is_readable( $file ) ) {
			throw new BackWPup_Destination_Dropbox_API_Exception(
				sprintf(
					// translators: %s = File name.
					esc_html__( "Error: File \"%s\" is not readable or doesn't exist.", 'backwpup' ),
					esc_html( $file )
				)
			);
		}

		if ( filesize( $file ) < 5242880 ) { // Chunk transfer on bigger uploads.
			return $this->files_upload(
				[
					'contents' => $this->get_file_contents( $file ),
					'path'     => $path,
					'mode'     => ( $overwrite ) ? 'overwrite' : 'add',
				]
			);
		}

		return $this->multipart_upload( $file, $path, $overwrite );
	}

	/**
	 * Uploads a file in multiple parts.
	 *
	 * @param string $file      File path.
	 * @param string $path      Destination path.
	 * @param bool   $overwrite Whether to overwrite existing file.
	 *
	 * @return array|mixed|string
	 * @throws BackWPup_Destination_Dropbox_API_Exception When upload fails.
	 */
	public function multipart_upload( $file, $path = '', $overwrite = true ) {
		$file = str_replace( '\\', '/', (string) $file );

		if ( ! is_readable( $file ) ) {
			throw new BackWPup_Destination_Dropbox_API_Exception(
				sprintf(
					// translators: %s = File name.
					esc_html__( "Error: File \"%s\" is not readable or doesn't exist.", 'backwpup' ),
					esc_html( $file )
				)
			);
		}

		$chunk_size = 4194304; // 4194304 = 4MB.

		$file_handle = fopen( $file, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( ! $file_handle ) {
			throw new BackWPup_Destination_Dropbox_API_Exception( esc_html__( 'Can not open source file for transfer.', 'backwpup' ) );
		}

		if ( ! isset( $this->job_object->steps_data[ $this->job_object->step_working ]['uploadid'] ) ) {
			$this->job_object->log( __( 'Beginning new file upload session', 'backwpup' ) );
			$session = $this->files_upload_session_start(
			);
			$this->job_object->steps_data[ $this->job_object->step_working ]['uploadid'] = $session['session_id'];
		}
		if ( ! isset( $this->job_object->steps_data[ $this->job_object->step_working ]['offset'] ) ) {
			$this->job_object->steps_data[ $this->job_object->step_working ]['offset'] = 0;
		}
		if ( ! isset( $this->job_object->steps_data[ $this->job_object->step_working ]['totalread'] ) ) {
			$this->job_object->steps_data[ $this->job_object->step_working ]['totalread'] = 0;
		}

		// seek to current position.
		if ( $this->job_object->steps_data[ $this->job_object->step_working ]['offset'] > 0 ) {
			fseek( $file_handle, $this->job_object->steps_data[ $this->job_object->step_working ]['offset'] );
		}

        while ($data = fread($file_handle, $chunk_size)) { // phpcs:ignore
			$chunk_upload_start = microtime( true );

			if ( $this->job_object->is_debug() ) {
				$this->job_object->log(
					// translators: %s is the size of the data.
					sprintf( __( 'Uploading %s of data', 'backwpup' ), size_format( strlen( $data ) ) )
				);
			}

			$this->files_upload_session_append_v2(
				[
					'contents' => $data,
					'cursor'   => [
						'session_id' => $this->job_object->steps_data[ $this->job_object->step_working ]['uploadid'],
						'offset'     => $this->job_object->steps_data[ $this->job_object->step_working ]['offset'],
					],
				]
			);
			$chunk_upload_time = microtime(
				true
			) - $chunk_upload_start;
			$this->job_object->steps_data[ $this->job_object->step_working ]['totalread'] += strlen( $data );

			// args for next chunk.
			$this->job_object->steps_data[ $this->job_object->step_working ]['offset'] += $chunk_size;
			if ( 'archive' === $this->job_object->job['backuptype'] ) {
				$this->job_object->substeps_done = $this->job_object->steps_data[ $this->job_object->step_working ]['offset'];
				if ( strlen( $data ) === $chunk_size ) {
					$time_remaining = $this->job_object->do_restart_time();
					// calc next chunk.
					if ( $time_remaining < $chunk_upload_time ) {
						$chunk_size = floor( $chunk_size / $chunk_upload_time * ( $time_remaining - 3 ) );
						if ( $chunk_size < 0 ) {
							$chunk_size = 1024;
						}
						if ( $chunk_size > 4194304 ) {
							$chunk_size = 4194304;
						}
					}
				}
			}
			$this->job_object->update_working_data();
			// correct position.
			fseek( $file_handle, $this->job_object->steps_data[ $this->job_object->step_working ]['offset'] );
		}

		fclose( $file_handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		$this->job_object->log(
			sprintf(
				// translators: %s is the size of the data.
				__( 'Finishing upload session with a total of %s uploaded', 'backwpup' ),
				size_format( $this->job_object->steps_data[ $this->job_object->step_working ]['totalread'] )
			)
		);

		$response = $this->files_upload_session_finish(
			[
				'cursor' => [
					'session_id' => $this->job_object->steps_data[ $this->job_object->step_working ]['uploadid'],
					'offset'     => $this->job_object->steps_data[ $this->job_object->step_working ]['totalread'],
				],
				'commit' => [
					'path' => $path,
					'mode' => ( $overwrite ) ? 'overwrite' : 'add',
				],
			]
		);

		unset( $this->job_object->steps_data[ $this->job_object->step_working ]['uploadid'], $this->job_object->steps_data[ $this->job_object->step_working ]['offset'] );

		return $response;
	}

	/**
	 * Set the OAuth tokens for this request.
	 *
	 * @param array    $token    The array with access and refresh tokens.
	 * @param callable $listener The callback to be called when a new token is fetched.
	 *
	 * @throws BackWPup_Destination_Dropbox_API_Exception When tokens are missing.
	 */
	public function set_o_auth_tokens( array $token, $listener = null ) {
		if ( empty( $token['access_token'] ) ) {
			throw new BackWPup_Destination_Dropbox_API_Exception(
				esc_html__( 'No access token provided', 'backwpup' )
			);
		}

		if ( empty( $token['refresh_token'] ) ) {
			throw new BackWPup_Destination_Dropbox_API_Exception(
				esc_html__( 'No refresh token provided. You may need to reauthenticate with Dropbox', 'backwpup' )
			);
		}

		$this->oauth_token = $token;

		if ( isset( $listener ) && is_callable( $listener ) ) {
			$this->listener = $listener;
		}

		if ( isset( $token['expires'] ) && time() > $token['expires'] ) {
			$token = $this->refresh( $token['refresh_token'] );
			$this->notify_refresh( $token );
		}
	}

	/**
	 * Get the current token array.
	 *
	 * Also modifies expires_in to match how much time is left until the access token expires.
	 *
	 * @throws \BadMethodCallException If tokens have not been set.
	 *
	 * @return array The token array.
	 */
	public function get_tokens() {
		$now    = time();
		$tokens = $this->oauth_token;
		if ( empty( $tokens ) ) {
			throw new \BadMethodCallException(
				esc_html__( 'OAuth tokens have not been set.', 'backwpup' )
			);
		}

		if ( $tokens['expires'] > $now ) {
			$tokens['expires_in'] = $tokens['expires'] - $now;
		} else {
			$tokens = $this->refresh( $tokens['refresh_token'] );
			$this->notify_refresh( $tokens );
		}

		return $tokens;
	}

	/**
	 * Returns the URL to authorize the user.
	 *
	 * @return string The authorization URL.
	 */
	public function o_auth_authorize() {
		return self::API_WWW_URL . 'oauth2/authorize?response_type=code&client_id=' . $this->oauth_app_key . '&token_access_type=offline';
	}

	/**
	 * Takes the oauth code and returns the access token.
	 *
	 * @param string $code The OAuth code.
	 *
	 * @return array An array including the access token, account ID, expiration, and other information.
	 */
	public function o_auth_token( $code ) {
		$token = $this->request(
			'oauth2/token',
			[
				'code'       => trim( $code ),
				'grant_type' => 'authorization_code',
			],
			'oauth'
		);

		$token['expires'] = time() + $token['expires_in'];

		return $token;
	}

	/**
	 * Returns a new access token given the refresh token.
	 *
	 * @param string $refresh_token The refresh token.
	 *
	 * @return array An array including the access token, account ID, expiration, and other information.
	 */
	public function refresh( $refresh_token ) {
		$token = $this->request(
			'oauth2/token',
			[
				'refresh_token' => trim( $refresh_token ),
				'grant_type'    => 'refresh_token',
			],
			'oauth'
		);

		$token['expires'] = time() + $token['expires_in'];

		$this->oauth_token = array_merge( $this->oauth_token, $token );

		return $this->oauth_token;
	}

	/**
	 * Notifies the listener that the access token was refreshed.
	 *
	 * @param array $token The new token.
	 *
	 * @return void
	 */
	private function notify_refresh( array $token ) {
		if ( isset( $this->listener ) ) {
			call_user_func( $this->listener, $token );
		}
	}

	/**
	 * Revokes the auth token.
	 *
	 * @return array
	 */
	public function auth_token_revoke() {
		return $this->request( 'auth/token/revoke' );
	}

	/**
	 * Download.
	 *
	 * @param array    $args       Argument for the API request.
	 * @param int|null $start_byte Start byte offset.
	 * @param int|null $end_byte   End byte offset.
	 *
	 * @throws BackWPup_Destination_Dropbox_API_Exception Because of a rate limit.
	 *
	 * @return mixed Whatever the API request returns.
	 */
	public function download( $args, $start_byte = null, $end_byte = null ) {
		$args['path'] = $this->format_path( $args['path'] );

		if ( null !== $start_byte && null !== $end_byte ) {
			return $this->request( 'files/download', $args, 'download', false, "{$start_byte}-{$end_byte}" );
		}

		return $this->request( 'files/download', $args, 'download' );
	}

	/**
	 * Deletes a file.
	 *
	 * @param array $args An array of arguments.
	 *
	 * @return array|null Information on the deleted file.
	 */
	public function files_delete( $args ): ?array {
		$args['path'] = $this->format_path( $args['path'] );

		try {
			return $this->request( 'files/delete', $args );
		} catch ( BackWPup_Destination_Dropbox_API_Request_Exception $e ) {
			$this->handle_files_delete_error( $e->getError() );

			return null;
		}
	}

	/**
	 * Gets the metadata of a file.
	 *
	 * @param array $args An array of arguments.
	 *
	 * @return array|null The file's metadata.
	 */
	public function files_get_metadata( $args ): ?array {
		$args['path'] = $this->format_path( $args['path'] );

		try {
			return $this->request( 'files/get_metadata', $args );
		} catch ( BackWPup_Destination_Dropbox_API_Request_Exception $e ) {
			$this->handle_files_get_metadata_error( $e->getError() );

			return null;
		}
	}

	/**
	 * Gets a temporary link from Dropbox to access the file.
	 *
	 * @param array $args An array of arguments.
	 *
	 * @return array|null Information on the file and link.
	 */
	public function files_get_temporary_link( $args ): ?array {
		$args['path'] = $this->format_path( $args['path'] );

		try {
			return $this->request( 'files/get_temporary_link', $args );
		} catch ( BackWPup_Destination_Dropbox_API_Request_Exception $e ) {
			$this->handle_files_get_temporary_link_error( $e->getError() );

			return null;
		}
	}

	/**
	 * Lists all the files within a folder.
	 *
	 * @param array $args An array of arguments.
	 *
	 * @return array|null A list of files.
	 */
	public function files_list_folder( $args ): ?array {
		$args['path'] = $this->format_path( $args['path'] );

		try {
			return $this->request( 'files/list_folder', $args );
		} catch ( BackWPup_Destination_Dropbox_API_Request_Exception $e ) {
			$this->handle_files_list_folder_error( $e->getError() );

			return null;
		}
	}

	/**
	 * Continue to list more files.
	 *
	 * When a folder has a lot of files, the API won't return all at once.
	 * So this method is to fetch more of them.
	 *
	 * @param array $args An array of arguments.
	 *
	 * @return array|null An array of files.
	 */
	public function files_list_folder_continue( $args ): ?array {
		try {
			return $this->request( 'files/list_folder/continue', $args );
		} catch ( BackWPup_Destination_Dropbox_API_Request_Exception $e ) {
			$this->handle_files_list_folder_continue_error( $e->getError() );

			return null;
		}
	}

	/**
	 * Uploads a file to Dropbox.
	 *
	 * The file must be no greater than 150 MB.
	 *
	 * @param array $args An array of arguments.
	 *
	 * @return array|null The uploaded file's information.
	 */
	public function files_upload( $args ) {
		$args['path'] = $this->format_path( $args['path'] );

		if ( isset( $args['client_modified'] )
			&& $args['client_modified'] instanceof DateTime
		) {
			$args['client_modified'] = $args['client_modified']->format( 'Y-m-d\TH:m:s\Z' );
		}

		try {
			return $this->request( 'files/upload', $args, 'upload' );
		} catch ( BackWPup_Destination_Dropbox_API_Request_Exception $e ) {
			$this->handle_files_upload_error( $e->getError() );

			return null;
		}
	}

	/**
	 * Append more data to an uploading file.
	 *
	 * @param array $args An array of arguments.
	 *
	 * @return array|null
	 */
	public function files_upload_session_append_v2( $args ) {
		try {
			return $this->request(
				'files/upload_session/append_v2',
				$args,
				'upload'
			);
		} catch ( BackWPup_Destination_Dropbox_API_Request_Exception $e ) {
			$error = $e->getError();

			// See if we can fix the error first.
			if ( 'incorrect_offset' === $error['.tag'] ) {
				$args['cursor']['offset'] = $error['correct_offset'];

				return $this->request(
					'files/upload_session/append_v2',
					$args,
					'upload'
				);
			}

			// Otherwise, cannot fix.
			$this->handle_files_upload_session_lookup_error( $error );
		}
	}

	/**
	 * Finish an upload session.
	 *
	 * @param array $args An array of arguments.
	 *
	 * @return array|null Information on the uploaded file.
	 */
	public function files_upload_session_finish( $args ): ?array {
		$args['commit']['path'] = $this->format_path( $args['commit']['path'] );

		try {
			return $this->request( 'files/upload_session/finish', $args, 'upload' );
		} catch ( BackWPup_Destination_Dropbox_API_Request_Exception $e ) {
			$error = $e->getError();
			if ( 'lookup_failed' === $error['.tag'] ) {
				if ( 'incorrect_offset' === $error['lookup_failed']['.tag'] ) {
					$args['cursor']['offset'] = $error['lookup_failed']['correct_offset'];

					return $this->request( 'files/upload_session/finish', $args, 'upload' );
				}
			}
			$this->handle_files_upload_session_finish_error( $e->getError() );

			return null;
		}
	}

	/**
	 * Starts an upload session.
	 *
	 * When a file larger than 150 MB needs to be uploaded, then this API
	 * endpoint is used to start a session to allow the file to be uploaded in
	 * chunks.
	 *
	 * @param array $args An array of arguments.
	 *
	 * @return array An array containing the session's ID.
	 */
	public function files_upload_session_start( $args = [] ) {
		return $this->request( 'files/upload_session/start', $args, 'upload' );
	}

	/**
	 * Get user's current account info.
	 *
	 * @return array
	 */
	public function users_get_current_account() {
		return $this->request( 'users/get_current_account' );
	}

	/**
	 * Get quota info for this user.
	 *
	 * @return array
	 */
	public function users_get_space_usage() {
		return $this->request( 'users/get_space_usage' );
	}

	/**
	 * Get the user agent.
	 *
	 * If no user agent has been provided, defaults to `BackWPup::get_plugin_data('User-Agent')`.
	 *
	 * @return string The user agent
	 */
	public function get_user_agent() {
		return $this->user_agent ?: \BackWPup::get_plugin_data( 'User-Agent' );
	}

	/**
	 * Set the user agent.
	 *
	 * @param string $user_agent User agent string.
	 */
	public function set_user_agent( $user_agent ) {
		$this->user_agent = $user_agent;
	}

	/**
	 * Get the SSL ca-bundle path.
	 *
	 * If no ca-bundle has been provided, defaults to `BackWPup::get_plugin_data('cacert')`.
	 *
	 * @return string The SSL ca-bundle
	 */
	public function get_ca_bundle() {
		return $this->ca_bundle ?: \BackWPup::get_plugin_data( 'cacert' );
	}

	/**
	 * Set the path to the SSL ca-bundle.
	 *
	 * @param string $ca_bundle The path to the ca-bundle file.
	 */
	public function set_ca_bundle( $ca_bundle ) {
		$this->ca_bundle = $ca_bundle;
	}

	/**
	 * Set the job object.
	 *
	 * @param BackWPup_Job $job_object The job object to set.
	 *
	 * @return void
	 */
	public function set_job_object( BackWPup_Job $job_object ) {
		$this->job_object = $job_object;
	}

	/**
	 * Logs a message to the current job.
	 *
	 * @param string $message The message to log.
	 * @param int    $level   The log level.
	 *
	 * @return bool|null True on success, null if no job object set.
	 */
	protected function log( $message, $level = E_USER_NOTICE ) {
		if ( ! isset( $this->job_object ) ) {
			return null;
		}

		return $this->job_object->log( $message, $level );
	}

	/**
	 * Logs debug info about the current request.
	 *
	 * @param string $endpoint The current request endpoint.
	 * @param array  $args     The request args.
	 *
	 * @return bool|null True on success, null if no job object set or debug is not enabled.
	 */
	protected function log_request( $endpoint, array $args ) {
		if ( ! isset( $this->job_object ) || ! $this->job_object->is_debug() ) {
			return null;
		}

		$message = "Call to {$endpoint}";

		if ( isset( $args['contents'] ) ) {
			$message .= ' with ' . size_format( strlen( (string) $args['contents'] ) ) . ' of data,';
			unset( $args['contents'] );
		}

		if ( ! empty( $args ) ) {
			$message .= ' with parameters ' . wp_json_encode( $args );
		}

		return $this->log( $message );
	}

	/**
	 * Request.
	 *
	 * @param string      $endpoint        API endpoint.
	 * @param array       $args            Request arguments.
	 * @param string      $endpoint_format Endpoint format.
	 * @param bool        $should_echo     Whether to echo response body.
	 * @param string|null $bytes           Byte range header value.
	 *
	 * @throws BackWPup_Destination_Dropbox_API_Exception When the request fails.
	 *
	 * @return array|mixed|string
	 */
	public function request( $endpoint, $args = [], $endpoint_format = 'rpc', $should_echo = false, $bytes = null ) {
		// Log request.
		$this->log_request( $endpoint, $args );

		if ( null !== $bytes ) {
			$args['bytes'] = $bytes;
		}

		$request = $this->build_request( $endpoint, $args, $endpoint_format );
		$client  = $this->create_client();

		$response = $client->sendRequest( $request );

		if ( $response->getStatusCode() >= 500 ) {
			$this->handle_server_exception( $response );
		} elseif ( $response->getStatusCode() >= 400 ) {
			$this->handle_request_exception( $response );

			// If we're still here, then recurse.
			return $this->request( $endpoint, $args, $endpoint_format, $should_echo, $bytes );
		}

		if ( true === $should_echo ) {
            echo $response->getBody(); // phpcs:ignore
		}

		if ( 'application/json' === $response->getHeaderLine( 'Content-Type' ) ) {
			return json_decode( $response->getBody(), true );
		}

		return $response->getBody()->getContents();
	}

	/**
	 * Gets the full URL to the Dropbox endpoint.
	 *
	 * @param string $endpoint The API endpoint.
	 * @param string $format   The endpoint format.
	 *
	 * @return string The full URL.
	 */
	private function get_url( $endpoint, $format ) {
		Assert::oneOf( $format, [ 'oauth', 'rpc', 'upload', 'download' ] );

		switch ( $format ) {
			case 'oauth':
				return self::API_URL . $endpoint;

			case 'rpc':
				return self::API_URL . self::API_VERSION_URL . $endpoint;

			default:
				return self::API_CONTENT_URL . self::API_VERSION_URL . $endpoint;
		}
	}

	/**
	 * Builds the options for the request.
	 *
	 * @param string $endpoint The endpoint to call.
	 * @param array  $args     The arguments for the request.
	 * @param string $format   The endpoint format.
	 *
	 * @return RequestInterface The HTTP request.
	 */
	private function build_request( $endpoint, array &$args, $format ) {
		$url = $this->get_url( $endpoint, $format );

		$request = $this->create_request_factory()
			->createRequest( 'POST', $url );

		if ( 'oauth' !== $format ) {
			$request = new AuthorizationRequest( $request );
			$request = $request->withOAuthToken( $this->get_tokens()['access_token'] );
		}

		$stream_factory = new StreamFactory();

		switch ( $format ) {
			case 'oauth':
				$request = new AuthorizationRequest( new FormRequest( $request ) );
				$request = $request
					->withBasicAuth( BasicAuthCredentials::fromUsernameAndPassword( $this->oauth_app_key, $this->oauth_app_secret ) )
					->withFormParams( $args, $stream_factory )
					->withHeader( 'Accept', 'application/json' );
				break;

			case 'rpc':
				$request = new JsonRequest( $request );
				$request = $request
					->withJsonData( $args ?: null, $stream_factory )
					->withHeader( 'Accept', 'application/json' );
				break;

			case 'upload':
				if ( isset( $args['contents'] ) ) {
					$stream  = $stream_factory->createStream( $args['contents'] );
					$request = $request->withBody( $stream );
					unset( $args['contents'] );
				}

				$request = $request
					->withHeader( 'Content-Type', 'application/octet-stream' )
					->withHeader( 'Dropbox-API-Arg', wp_json_encode( $args, JSON_FORCE_OBJECT ) );
				break;

			case 'download':
				if ( isset( $args['bytes'] ) ) {
					$request = $request
						->withHeader( 'Range', 'bytes=' . $args['bytes'] );
					unset( $args['bytes'] );
				}

				$request = $request
					->withHeader( 'Content-Type', 'text/plain' )
					->withHeader( 'Accept', 'application/octet-stream' )
					->withHeader( 'Dropbox-API-Arg', wp_json_encode( $args, JSON_FORCE_OBJECT ) );
				break;
		}

		return $request;
	}

	/**
	 * Handle request exception.
	 *
	 * Called for 4xx responses.
	 *
	 * @param ResponseInterface $response The returned response.
	 *
	 * @throws BackWPup_Destination_Dropbox_API_Exception If the error cannot be handled.
	 */
	private function handle_request_exception( ResponseInterface $response ) {
		switch ( $response->getStatusCode() ) {
			case 400:
			case 401:
			case 403:
			case 409:
			case 429:
				$callback = [ $this, 'handle_' . $response->getStatusCode() . '_error' ];
				call_user_func( $callback, $response );
				break;

			default:
				throw new BackWPup_Destination_Dropbox_API_Exception(
					sprintf(
						// translators: %1$s is the error code, %2$s is the response body.
						esc_html__(
							'(%1$s) An unknown error has occurred. Response from server: %2$s',
							'backwpup'
						),
						esc_html( (string) $response->getStatusCode() ),
						esc_html( $response->getBody()->getContents() )
					)
				);
		}
	}

	/**
	 * Handle server exception.
	 *
	 * Called for 5xx responses.
	 *
	 * @param ResponseInterface $response The returned response.
	 *
	 * @throws BackWPup_Destination_Dropbox_API_Exception When the server returns an error.
	 */
	protected function handle_server_exception( ResponseInterface $response ) {
		throw new BackWPup_Destination_Dropbox_API_Exception(
			sprintf(
				// translators: %1$d is the error code, %2$s is the response body.
				esc_html__(
					'(%1$d) An unexpected server error was encountered. Response from server: %2$s',
					'backwpup'
				),
				esc_html( (string) $response->getStatusCode() ),
				esc_html( $response->getBody()->getContents() )
			)
		);
	}

	/**
	 * Handle 400 response error.
	 *
	 * @param \Psr\Http\Message\ResponseInterface $response The returned response.
	 *
	 * @throws BackWPup_Destination_Dropbox_API_Exception When the request is invalid.
	 */
	protected function handle_400_error( ResponseInterface $response ) {
		throw new BackWPup_Destination_Dropbox_API_Exception(
			sprintf(
				// translators: %s is the response body.
				esc_html__(
					'(400) Bad input parameter. Response from server: %s',
					'backwpup'
				),
				esc_html( $response->getBody()->getContents() )
			)
		);
	}

	/**
	 * Handle 401 response error.
	 *
	 * @param \Psr\Http\Message\ResponseInterface $response The returned response.
	 *
	 * @throws BackWPup_Destination_Dropbox_API_Exception If token is invalid.
	 */
	protected function handle_401_error( ResponseInterface $response ) {
		$error = json_decode( $response->getBody()->getContents(), true );
		if ( 'expired_access_token' === $error['error']['.tag'] ) {
			$this->refresh( $this->oauth_token['refresh_token'] );
		} else {
			throw new BackWPup_Destination_Dropbox_API_Exception(
				sprintf(
					// translators: %s is the response error.
					esc_html__(
						'(401) Bad or expired token. Response from server: %s',
						'backwpup'
					),
					esc_html( $error['error']['.tag'] )
				)
			);
		}
	}

	/**
	 * Handle 403 response error.
	 *
	 * @param \Psr\Http\Message\ResponseInterface $response The returned response.
	 *
	 * @throws BackWPup_Destination_Dropbox_API_Exception When access is denied.
	 */
	protected function handle_403_error( ResponseInterface $response ) {
		$error = json_decode( $response->getBody(), true );

		if ( 'invalid_account_type' === $error['error']['.tag'] ) {
			// InvalidAccountTypeError.
			if ( 'endpoint' === $error['error']['invalid_account_type']['.tag'] ) {
				throw new BackWPup_Destination_Dropbox_API_Exception(
					esc_html__(
						'(403) You do not have permission to access this endpoint.',
						'backwpup'
					)
				);
			}
			if ( 'feature' === $error['error']['invalid_account_type']['.tag'] ) {
				throw new BackWPup_Destination_Dropbox_API_Exception(
					esc_html__(
						'(403) You do not have permission to access this feature.',
						'backwpup'
					)
				);
			}
		}

		// Catch all.
		throw new BackWPup_Destination_Dropbox_API_Exception(
			sprintf(
				// translators: %s is the response error.
				esc_html__(
					'(403) You do not have permission to access this resource. Response from server: %s',
					'backwpup'
				),
				esc_html( $error['error_summary'] )
			)
		);
	}

	/**
	 * Handle 409 response error.
	 *
	 * @param \Psr\Http\Message\ResponseInterface $response The returned response.
	 *
	 * @throws BackWPup_Destination_Dropbox_API_Request_Exception For endpoint-specific errors.
	 */
	protected function handle_409_error( ResponseInterface $response ) {
		$error = json_decode( $response->getBody(), true );

		throw new BackWPup_Destination_Dropbox_API_Request_Exception(
			sprintf(
				// translators: %s is the response error.
				esc_html__(
					'(409) Endpoint-specific error. Response from server: %s',
					'backwpup'
				),
				esc_html( $error['error_summary'] )
			),
			(int) esc_html( (string) $response->getStatusCode() ),
			null,
			$error['error'] // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Error details are stored on the exception, not output.
		);
	}

	/**
	 * Handle 429 response error.
	 *
	 * This error is encountered when requests are being rate limited.
	 *
	 * @param \Psr\Http\Message\ResponseInterface $response The returned response.
	 *
	 * @throws BackWPup_Destination_Dropbox_API_Exception If unable to detect time to wait.
	 */
	protected function handle_429_error( ResponseInterface $response ) {
		if ( ! $response->hasHeader( 'Retry-After' ) ) {
			throw new BackWPup_Destination_Dropbox_API_Exception(
				esc_html__(
					'(429) Requests are being rate limited. Please try again later.',
					'backwpup'
				)
			);
		}
		sleep( (int) $response->getHeaderLine( 'Retry-After' ) );
	}

	/**
	 * Creates a new HTTP client.
	 *
	 * @return ClientInterface
	 */
	protected function create_client() {
		$options = [
			'timeout' => 60,
		];

		if ( empty( $this->get_ca_bundle() ) ) {
			$options['sslverify'] = false;
		} else {
			$options += [
				'sslverify'       => true,
				'sslcertificates' => $this->get_ca_bundle(),
			];
		}

		if ( ! empty( $this->get_user_agent() ) ) {
			$options['user-agent'] = $this->get_user_agent();
		}

		return new WpHttpClient( new ResponseFactory(), new StreamFactory(), $options );
	}

	/**
	 * Creates a request factory for creating requests.
	 *
	 * @return RequestFactory
	 */
	protected function create_request_factory() {
		return new RequestFactory();
	}

	/**
	 * Formats a path to be valid for Dropbox.
	 *
	 * @param string $path Path to format.
	 *
	 * @return string The formatted path.
	 */
	private function format_path( $path ) {
		if ( ! empty( $path ) && '/' !== substr( $path, 0, 1 ) ) {
			$path = '/' . rtrim( $path, '/' );
		} elseif ( '/' === $path ) {
			$path = '';
		}

		return $path;
	}

	/**
	 * Reads a local file using the WordPress filesystem API.
	 *
	 * @param string $file File path.
	 *
	 * @return string File contents.
	 * @throws BackWPup_Destination_Dropbox_API_Exception When the file cannot be read.
	 */
	private function get_file_contents( string $file ): string {
		global $wp_filesystem;

		if ( ! $wp_filesystem ) {
			if ( ! function_exists( 'WP_Filesystem' ) ) {
				$filesystem_path = '';
				if ( defined( 'ABSPATH' ) && is_string( ABSPATH ) ) {
					$filesystem_path = rtrim( ABSPATH, '/\\' ) . '/wp-admin/includes/file.php';
				}

				if ( '' !== $filesystem_path && file_exists( $filesystem_path ) ) {
					require_once $filesystem_path;
				}
			}

			if ( function_exists( 'WP_Filesystem' ) ) {
				WP_Filesystem();
			}
		}

		if ( ! $wp_filesystem instanceof \WP_Filesystem_Base ) {
			throw new BackWPup_Destination_Dropbox_API_Exception(
				esc_html__( 'File system could not be initialized.', 'backwpup' )
			);
		}

		$contents = $wp_filesystem->get_contents( $file );
		if ( false === $contents ) {
			throw new BackWPup_Destination_Dropbox_API_Exception(
				esc_html__( 'Cannot read file contents.', 'backwpup' )
			);
		}

		return $contents;
	}

	// Error handlers.

	/**
	 * Logs a Dropbox API warning message.
	 *
	 * @param string $message Warning message.
	 *
	 * @return void
	 */
	private function log_warning( string $message ): void {
		if ( $this->job_object instanceof BackWPup_Job ) {
			$this->job_object->log( $message, E_USER_WARNING );
			return;
		}

		/**
		 * Fires when the Dropbox API emits a warning outside of a job context.
		 *
		 * @param string $message Warning message.
		 * @param BackWPup_Destination_Dropbox_API $client API client instance.
		 */
		do_action( 'backwpup_dropbox_api_warning', $message, $this );
	}

	/**
	 * Handle file delete errors.
	 *
	 * @param array $error Error payload.
	 *
	 * @return void
	 */
	private function handle_files_delete_error( $error ) {
		switch ( $error['.tag'] ) {
			case 'path_lookup':
				$this->handle_files_lookup_error( $error['path_lookup'] );
				break;

			case 'path_write':
				$this->handle_files_write_error( $error['path_write'] );
				break;

			case 'other':
				$this->log_warning( 'Could not delete file.' );
				break;
		}
	}

	/**
	 * Handle file metadata errors.
	 *
	 * @param array $error Error payload.
	 *
	 * @return void
	 */
	private function handle_files_get_metadata_error( $error ) {
		switch ( $error['.tag'] ) {
			case 'path':
				$this->handle_files_lookup_error( $error['path'] );
				break;

			case 'other':
				$this->log_warning( 'Cannot look up file metadata.' );
				break;
		}
	}

	/**
	 * Handle temporary link errors.
	 *
	 * @param array $error Error payload.
	 *
	 * @return void
	 */
	private function handle_files_get_temporary_link_error( $error ) {
		switch ( $error['.tag'] ) {
			case 'path':
				$this->handle_files_lookup_error( $error['path'] );
				break;

			case 'other':
				$this->log_warning( 'Cannot get temporary link.' );
				break;
		}
	}

	/**
	 * Handle list folder errors.
	 *
	 * @param array $error Error payload.
	 *
	 * @return void
	 */
	private function handle_files_list_folder_error( $error ) {
		switch ( $error['.tag'] ) {
			case 'path':
				$this->handle_files_lookup_error( $error['path'] );
				break;

			case 'other':
				$this->log_warning( 'Cannot list files in folder.' );
				break;
		}
	}

	/**
	 * Handle list folder continue errors.
	 *
	 * @param array $error Error payload.
	 *
	 * @return void
	 */
	private function handle_files_list_folder_continue_error( $error ) {
		switch ( $error['.tag'] ) {
			case 'path':
				$this->handle_files_lookup_error( $error['path'] );
				break;

			case 'reset':
				$this->log_warning( 'This cursor has been invalidated.' );
				break;

			case 'other':
				$this->log_warning( 'Cannot list files in folder.' );
				break;
		}
	}

	/**
	 * Handle lookup errors.
	 *
	 * @param array $error Error payload.
	 *
	 * @return void
	 */
	private function handle_files_lookup_error( $error ) {
		switch ( $error['.tag'] ) {
			case 'malformed_path':
				$this->log_warning( 'The path was malformed.' );
				break;

			case 'not_found':
				$this->log_warning( 'File could not be found.' );
				break;

			case 'not_file':
				$this->log_warning( 'That is not a file.' );
				break;

			case 'not_folder':
				$this->log_warning( 'That is not a folder.' );
				break;

			case 'restricted_content':
				$this->log_warning( 'This content is restricted.' );
				break;

			case 'invalid_path_root':
				$this->log_warning( 'Path root is invalid.' );
				break;

			case 'other':
				$this->log_warning( 'File could not be found.' );
				break;
		}
	}

	/**
	 * Handle upload session finish errors.
	 *
	 * @param array $error Error payload.
	 *
	 * @return void
	 */
	private function handle_files_upload_session_finish_error( $error ) {
		switch ( $error['.tag'] ) {
			case 'lookup_failed':
				$this->handle_files_upload_session_lookup_error(
					$error['lookup_failed']
				);
				break;

			case 'path':
				$this->handle_files_write_error( $error['path'] );
				break;

			case 'too_many_shared_folder_targets':
				$this->log_warning( 'Too many shared folder targets.' );
				break;

			case 'other':
				$this->log_warning( 'The file could not be uploaded.' );
				break;
		}
	}

	/**
	 * Handle upload session lookup errors.
	 *
	 * @param array $error Error payload.
	 *
	 * @return void
	 */
	private function handle_files_upload_session_lookup_error( $error ) {
		switch ( $error['.tag'] ) {
			case 'not_found':
				$this->log_warning( 'Session not found.' );
				break;

			case 'incorrect_offset':
				$this->log_warning(
					'Incorrect offset given. Correct offset is ' .
					intval( $error['correct_offset'] ) . '.'
				);
				break;

			case 'closed':
				$this->log_warning( 'This session has been closed already.' );
				break;

			case 'not_closed':
				$this->log_warning( 'This session is not closed.' );
				break;

			case 'other':
				$this->log_warning( 'Could not look up the file session.' );
				break;
		}
	}

	/**
	 * Handle upload errors.
	 *
	 * @param array $error Error payload.
	 *
	 * @return void
	 */
	private function handle_files_upload_error( $error ) {
		switch ( $error['.tag'] ) {
			case 'path':
				$this->handle_files_upload_write_failed( $error['path'] );
				break;

			case 'other':
				$this->log_warning( 'There was an unknown error when uploading the file.' );
				break;
		}
	}

	/**
	 * Handle upload write failed errors.
	 *
	 * @param array $error Error payload.
	 *
	 * @return void
	 */
	private function handle_files_upload_write_failed( $error ) {
		$this->handle_files_write_error( $error['reason'] );
	}

	/**
	 * Handle file write errors.
	 *
	 * @param array $error Error payload.
	 *
	 * @return void
	 */
	private function handle_files_write_error( $error ) {
		$message = '';

		// Type of error.
		switch ( $error['.tag'] ) {
			case 'malformed_path':
				$message = 'The path was malformed.';
				break;

			case 'conflict':
				$message = 'Cannot write to the target path due to conflict.';
				break;

			case 'no_write_permission':
				$message = 'You do not have permission to save to this location.';
				break;

			case 'insufficient_space':
				$message = 'You do not have enough space in your Dropbox.';
				break;

			case 'disallowed_name':
				$message = 'The given name is disallowed by Dropbox.';
				break;

			case 'team_folder':
				$message = 'Unable to modify team folders.';
				break;

			case 'other':
				$message = 'There was an unknown error when uploading the file.';
				break;
		}

		$this->log_warning( $message );
	}
}
