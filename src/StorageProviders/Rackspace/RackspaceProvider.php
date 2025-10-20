<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\StorageProviders\Rackspace;

use BackWPup_Admin;
use Exception;
use Inpsyde\BackWPupShared\File\MimeTypeExtractor;
use RuntimeException;
use WPMedia\BackWPup\Dependencies\GuzzleHttp\Client;

/**
 * Rackspace cloud storage provider.
 */
class RackspaceProvider {
	/**
	 * Rackspace US
	 *
	 * @var string
	 */
	private string $rackspace_us = 'https://identity.api.rackspacecloud.com/v2.0/';

	/**
	 * Rackspace UK
	 *
	 * @var string
	 */
	private string $rackspace_uk = 'https://lon.identity.api.rackspacecloud.com/v2.0/';

	/**
	 * Guzzle client.
	 *
	 * @var Client
	 */
	private $http_client = null;

	/**
	 * User auth token.
	 *
	 * @var string
	 */
	private string $auth_token;

	/**
	 * Cloud endpoint url.
	 *
	 * @var string
	 */
	private string $cloud_files_endpoint_url;

	/**
	 * Container name
	 *
	 * @var string
	 */
	private string $container_name;

	/**
	 * Credentials passed in by the user
	 *
	 * @var array
	 */
	private array $secret = [];

	/**
	 * Auth url
	 *
	 * @var string
	 */
	private string $auth_url;

	/**
	 * Api key
	 *
	 * @var string
	 */
	private string $api_key;

	/**
	 * Rackspace username
	 *
	 * @var string
	 */
	private string $username;

	/**
	 * Cloud region
	 *
	 * @var string
	 */
	private string $region;

	/**
	 * Constructor
	 *
	 * @param array $data The configuration data.
	 */
	public function __construct( array $data ) {
		$this->set_secret( $data );
		$this->set_username( $data['username'] );
		$this->set_api_key( $data['api_key'] );
		$this->set_container_name( $data['container_name'] );
		$this->set_region( $data['region'] );
		$this->set_auth_url( $data['region'] );
	}

	/**
	 * Set secret
	 *
	 * @param array $secret
	 *
	 * @return void
	 */
	public function set_secret( array $secret = [] ): void {
		$this->secret = $secret;
	}

	/**
	 * Set region
	 *
	 * @param string $region The cloud region.
	 */
	public function set_region( string $region ): void {
		$this->region = $region;
	}

	/**
	 * Get region
	 *
	 * @return string
	 */
	public function get_region(): string {
		return $this->region;
	}

	/**
	 * Set container
	 *
	 * @param string $container_name The container name.
	 *
	 * @return void
	 */
	public function set_container_name( string $container_name ): void {
		$this->container_name = $container_name;
	}

	/**
	 * Get container name
	 *
	 * @return string
	 */
	public function get_container_name(): string {
		return $this->container_name;
	}

	/**
	 * Get the secret.
	 *
	 * @return array
	 */
	public function get_secret(): array {
		return $this->secret;
	}


	/**
	 * Initialise the tenant using the supplied credentials
	 *
	 * @return void
	 * @throws Exception Throw guzzle exception if request failed.
	 */
	public function initialise(): void {
		$response = wp_remote_post(
			$this->get_auth_url() . 'tokens',
			[
				'timeout' => 30,
				'headers' => [
					'Content-Type' => 'application/json',
					'Accept'       => 'application/json',
				],
				'body'    => wp_json_encode(
					[
						'auth' => [
							'RAX-KSKEY:apiKeyCredentials' => [
								'username' => $this->get_username(),
								'apiKey'   => $this->get_api_key(),
							],
						],
					]
					),
			]
		);

		if ( is_wp_error( $response ) ) {
			/* translators: %s: error message from the response */
			throw new Exception( sprintf( esc_html__( 'Authentication request failed: %s ', 'backwpup' ), esc_html( $response->get_error_message() ) ) );
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $response_code ) {
			$error_message = wp_remote_retrieve_response_message( $response );
			throw new Exception(
				sprintf(
				/* translators: %1s: response code %1$s error message from the response */
					esc_html__( 'Authentication failed with HTTP %1$s %2$s ', 'backwpup' ),
					esc_html( $response_code ),
					esc_html( $error_message )
				)
			);
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body );

		if ( ! isset( $data->access->token->id ) ) {
			throw new Exception( 'Invalid authentication response structure' );
		}

		$this->set_token( $data->access->token->id );
		$this->set_endpoint_url( $data );
	}

	/**
	 * Set the endpoint url for Rackspace container.
	 *
	 * @param object $data Rackspace data.
	 *
	 * @return void
	 */
	public function set_endpoint_url( $data ): void {
		foreach ( $data->access->serviceCatalog as $service ) {
			if ( 'cloudFiles' === $service->name ) {
				foreach ( $service->endpoints as $endpoint ) {
					if ( $endpoint->region === $this->get_region() ) {
						$this->cloud_files_endpoint_url = $endpoint->publicURL; // phpcs:ignore
						break 2;
					}
				}
			}
		}
	}

	/**
	 * Get available containers.
	 *
	 * @return mixed
	 * @throws Exception If container request failed.
	 */
	public function get_containers() {
		try {
			$response = wp_remote_get(
				$this->cloud_files_endpoint_url,
				[
					'headers' => [
						'X-Auth-Token' => $this->get_token(),
						'Accept'       => 'application/json',
					],
					'timeout' => 30,
				]
				);

			if ( is_wp_error( $response ) ) {
				throw new Exception( 'Failed to get containers: ' . $response->get_error_message() );
			}

			if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
				throw new Exception( 'Failed to get containers with status: ' . wp_remote_retrieve_response_code( $response ) );
			}
		} catch ( \Exception $e ) {
			$error_message = sprintf(
			/* translators: 1: error code, 2: error message */
				__( 'Failed to get containers: %1$s', 'backwpup' ),
				$e->getMessage()
			);
			throw new Exception( esc_html( $error_message ) );
		}

		return json_decode( wp_remote_retrieve_body( $response ), true );
	}

	/**
	 * Set authenticated url
	 *
	 * @param string $region Cloud region.
	 * @return void
	 */
	public function set_auth_url( string $region ) {
		if ( 'LON' === $region ) {
			$this->auth_url = $this->rackspace_uk;

			return;
		}

		$this->auth_url = $this->rackspace_us;
	}

	/**
	 * Get authenticated url
	 *
	 * @return string
	 */
	public function get_auth_url(): string {
		return $this->auth_url;
	}

	/**
	 * Set api key
	 *
	 * @param string $api_key Api key.
	 *
	 * @return void
	 */
	public function set_api_key( string $api_key ): void {
		$this->api_key = $api_key;
	}

	/**
	 * Get api key
	 *
	 * @return string
	 */
	public function get_api_key() {
		return $this->api_key;
	}

	/**
	 * Set username
	 *
	 * @param string $username Set username.
	 *
	 * @return void
	 */
	public function set_username( string $username ): void {
		$this->username = $username;
	}

	/**
	 * Get username
	 *
	 * @return string
	 */
	public function get_username(): string {
		return $this->username;
	}

	/**
	 * Set auth token.
	 *
	 * @param string $token Token.
	 *
	 * @return void
	 */
	public function set_token( string $token ): void {
		$this->auth_token = $token;
	}

	/**
	 * Get auth token.
	 *
	 * @return string
	 */
	public function get_token(): string {

		return $this->auth_token;
	}

	/**
	 * Upload to cloud storage.
	 *
	 * @param string $filename The filename of the upload.
	 * @param mixed  $data The upload data.
	 *
	 * @return true
	 * @throws Exception If upload fails.
	 * @throws Exception If upload fails.
	 */
	public function upload_object( string $filename, $data ): bool {
		$handle       = fopen( $data, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$content_type = mime_content_type( $handle );
		$file_size    = filesize( $data );

		if ( false === $content_type ) {
			$content_type = MimeTypeExtractor::fromFilePath( $data );
		}

		/**
		 * Filters for the memory usage threshold, determine when to switch to chunked reading for upload.
		 *
		 * @since 5.0
		 *
		 * @param int $chunk_size The chunk size.
		 */
		$chunk_size = wpm_apply_filters_typed( 'integer', 'backwpup_rackspace_chunk_size', 20971520, );

		if ( $file_size < $chunk_size ) { // chunk transfer on bigger uploads.
			return $this->regular_upload( $data, $filename, $content_type );
		}

		return $this->multipart_upload( $handle, $filename, $content_type, $chunk_size, $file_size );
	}

	/**
	 * Upload files
	 *
	 * @param string $data Data to be uploaded.
	 * @param string $filename The filename.
	 * @param string $content_type Content type.
	 *
	 * @return bool
	 * @throws Exception Throw exception if upload fails.
	 */
	public function regular_upload( string $data, string $filename, string $content_type ): bool {
		try {
			$args = [
				'method'  => 'PUT',
				'timeout' => 600,
				'headers' => [
					'X-Auth-Token' => $this->get_token(),
					'Content-Type' => $content_type,
				],
				'body'    => file_get_contents( $data ), //phpcs:ignore
			];

			$endpoint = "{$this->cloud_files_endpoint_url}/{$this->container_name}/{$filename}";

			$response = wp_remote_request( $endpoint, $args );

			if ( is_wp_error( $response ) ) {
				throw new Exception( 'Upload failed: ' . $response->get_error_message() );
			}

			$status = wp_remote_retrieve_response_code( $response );
			if ( 200 > $status || 300 <= $status ) {
				throw new Exception( 'Upload failed with status: ' . $status );
			}
		} catch ( Exception $ex ) {
			$error_message = sprintf(
			/* translators: 1: error code, 2: error message */
				__( 'Error uploading file to Cloud Files. Code: %1$s message: %2$s', 'backwpup' ),
				$ex->getCode(),
				$ex->getMessage()
			);
			throw new Exception(
				esc_html( $error_message )
			);
		}

		return true;
	}

	/**
	 * Multipart upload
	 *
	 * @param mixed  $data The data to be uploaded.
	 * @param string $filename The filename.
	 * @param string $content_type Content type.
	 * @param int    $chunk_size Chunk size.
	 * @param int    $file_size File size.
	 * @return bool
	 *
	 * @throws Exception Throw exception if upload fails.
	 */
	public function multipart_upload( $data, string $filename, string $content_type, int $chunk_size, int $file_size ): bool {
		if ( ! $data ) {
			/* translators: %s: error message from the response */
			throw new Exception( sprintf( esc_html__( 'Cannot open file for reading: %s', 'backwpup' ), esc_html( $data ) ) );
		}
		$segments_uploaded = [];

		try {
			$total_read     = 0;
			$segment_number = 1;
			$segment_prefix = $filename . '/';

			while ( ! feof( $data ) && $total_read < $file_size ) {
				$remaining         = $file_size - $total_read;
				$current_read_size = min( $chunk_size, $remaining );
				$chunk             = fread( $data, $current_read_size ); //phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
				if ( false === $chunk ) {
					throw new Exception( 'Failed to read chunk from file' );
				}

				if ( strlen( $chunk ) === 0 ) {
					if ( feof( $data ) ) {
						break;
					} else {
						throw new Exception( 'Read empty chunk but not at end of file' );
					}
				}

				$segment_name = $segment_prefix . sprintf( '%08d', $segment_number );

				$this->chunk_upload( $chunk, $segment_name, $content_type, strlen( $chunk ) );
				$segments_uploaded[] = $segment_name;

				$total_read += strlen( $chunk );
				++$segment_number;
			}

			$this->create_manifest( $filename, $content_type, $segment_prefix );
		} catch ( Exception $e ) {
			// clean up uploaded segments if upload fails.
			$this->cleanup_segments( $segments_uploaded );

			/* translators: %s: error message from the exception */
			throw new Exception( sprintf( esc_html__( '%s during larger file upload', 'backwpup' ), esc_html( $e->getMessage() ) ) );
		} finally {
			fclose( $data ); //phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		}

		return true;
	}

	/**
	 * Create manifest after segment upload of backup.
	 *
	 * @param string $filename Filename.
	 * @param string $content_type The content type.
	 * @param string $segment_prefix The segment prefix.
	 *
	 * @return void
	 * @throws Exception Throw exception if request fail.
	 */
	private function create_manifest( string $filename, string $content_type, string $segment_prefix ): void {
		$args = [
			'method'  => 'PUT',
			'timeout' => 300,
			'headers' => [
				'X-Auth-Token'      => $this->get_token(),
				'Content-Type'      => $content_type,
				'Content-Length'    => 0,
				'X-Object-Manifest' => "{$this->container_name}/{$segment_prefix}",
			],
			'body'    => '',
		];

		$endpoint = "{$this->cloud_files_endpoint_url}/{$this->container_name}/{$filename}";
		$response = wp_remote_request( $endpoint, $args );

		if ( is_wp_error( $response ) ) {
			/* translators: %s: error message from the response */
			throw new Exception( sprintf( esc_html__( 'Manifest creation failed: %s ', 'backwpup' ), esc_html( $response->get_error_message() ) ) );
		}

		$status = wp_remote_retrieve_response_code( $response );
		if ( $status < 200 || $status >= 300 ) {
			$response_body = wp_remote_retrieve_body( $response );
			throw new Exception(
				sprintf(
				// translators: %s: status, %s response body.
					esc_html__( 'Manifest creation failed with status %1$s. Response: %2$s', 'backwpup' ),
					esc_html( $status ),
					esc_html( $response_body )
				)
			);
		}
	}

	/**
	 * Cleanup segment upload
	 *
	 * @param array $segment_names Segment names to clean up.
	 *
	 * @return void
	 * @throws Exception Throw exception if deletion failed.
	 */
	private function cleanup_segments( array $segment_names ): void {
		foreach ( $segment_names as $segment_name ) {
			try {
				$args = [
					'method'  => 'DELETE',
					'timeout' => 300,
					'headers' => [
						'X-Auth-Token' => $this->get_token(),
					],
				];

				$endpoint = "{$this->cloud_files_endpoint_url}/{$this->container_name}/{$segment_name}";
				wp_remote_request( $endpoint, $args );
			} catch ( Exception $e ) {
				// translators: %s: error response.
				throw new Exception( sprintf( esc_html__( 'Failed to delete segment: %s ', 'backwpup' ), esc_html( $e->getMessage() ) ) );
			}
		}
	}

	/**
	 * Chunk upload
	 *
	 * @param mixed  $file_content The content to be uploaded.
	 * @param string $filename The filename.
	 * @param string $content_type Content type.
	 * @param int    $file_size File size.
	 *
	 * @return void
	 * @throws Exception Throw exception when upload fails.
	 */
	private function chunk_upload( $file_content, string $filename, string $content_type, int $file_size ): void {
		$args = [
			'method'  => 'PUT',
			'timeout' => max( 300, $file_size / 1024000 ),
			'headers' => [
				'X-Auth-Token'   => $this->get_token(),
				'Content-Length' => $file_size,
			],
			'body'    => $file_content,
		];

		$endpoint = "{$this->cloud_files_endpoint_url}/{$this->container_name}/{$filename}";

		$response = wp_remote_request( $endpoint, $args );

		if ( is_wp_error( $response ) ) {
			/* translators: %s: error message from the response */
			throw new Exception( sprintf( esc_html__( 'Chunk upload failed: %s ', 'backwpup' ), esc_html( $response->get_error_message() ) ) );
		}

		$status = wp_remote_retrieve_response_code( $response );
		if ( $status < 200 || $status >= 300 ) {
			$response_body = wp_remote_retrieve_body( $response );
			throw new Exception(
				sprintf(
				// translators: %s: status, %s response body.
					esc_html__( 'Upload failed with status %1$s. Response: %2$s', 'backwpup' ),
					esc_html( $status ),
					esc_html( $response_body )
				)
			);
		}
	}

	/**
	 * Get object list.
	 *
	 * @param array $params Available parameter to pass (prefix, limit, format, path ).
	 *
	 * @return mixed
	 * @throws Exception Throw exception error if request fail.
	 */
	public function object_list( array $params = [] ) {
		$endpoint     = $this->cloud_files_endpoint_url . '/' . $this->get_container_name();
		$query_params = array_merge( [ 'format' => 'json' ], $params );

		try {
			$url      = add_query_arg( $query_params, $endpoint );
			$response = wp_remote_get(
				$url,
				[
					'headers' => [
						'X-Auth-Token' => $this->get_token(),
						'Accept'       => 'application/json',
					],
					'timeout' => 30,
				]
			);

			if ( is_wp_error( $response ) ) {
				throw new Exception( 'Failed to get object list: ' . $response->get_error_message() );
			}

			$status_code = wp_remote_retrieve_response_code( $response );
			if ( 200 !== $status_code ) {
				throw new Exception( 'Failed to get object list with status: ' . $status_code );
			}

			$body = wp_remote_retrieve_body( $response );

			return json_decode( $body, true );
		} catch ( \Exception $e ) {
			$error_message = sprintf(
			/* translators: 1: error message */
				__( 'Failed to get object list: %1$s', 'backwpup' ),
				$e->getMessage()
			);
			throw new Exception( esc_html( $error_message ) );
		}
	}

	/**
	 * Delete object in cloud storage
	 *
	 * @param string $object_name The object to be deleted.
	 * @param string $job_id The job id to be deleted.
	 *
	 * @return true
	 *
	 * @throws Exception When deletion process is complete.
	 */
	public function delete_object( string $object_name, string $job_id ): bool {
		try {
			$this->delete( $object_name );

			wp_schedule_single_event(
				time(),
				'backwpup_rsc_delete_segment_files',
				[ $object_name, $job_id ]
			);
			return true;
		} catch ( \Exception $e ) {
			BackWPup_Admin::message( 'RSC: ' . $e->getMessage(), true );
		}

		return false;
	}

	/**
	 * Create container
	 *
	 * @param string $container_name The container name.
	 *
	 * @return bool
	 * @throws Exception Throw exception if request fails.
	 */
	public function create_container( string $container_name ): bool {
		$url = "{$this->cloud_files_endpoint_url}/{$container_name}";

		$response = wp_remote_request(
			$url,
			[
				'method'  => 'PUT',
				'timeout' => 60,
				'headers' => [
					'X-Auth-Token' => $this->get_token(),
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			/* translators: 1: error message */
			throw new Exception( sprintf( __( 'Can\'t create  container: %1$s', 'backwpup' ), esc_html( $response->get_error_message() ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		$status = wp_remote_retrieve_response_code( $response );

		if ( 201 !== $status ) {
			/* translators: 1: error message */
			throw new Exception( sprintf( __( 'Container creation failed: %1$s', 'backwpup' ), esc_html( $status ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		return true;
	}

	/**
	 * Delete object.
	 *
	 * @param string $object_name The object name to be deleted.
	 *
	 * @return void
	 * @throws Exception Throw exception if deletion is not complete.
	 */
	public function delete( string $object_name ) {
		$url = "{$this->cloud_files_endpoint_url}/{$this->get_container_name()}/{$object_name}";

		$response = wp_remote_request(
			$url,
			[
				'method'  => 'DELETE',
				'timeout' => 60,
				'headers' => [
					'X-Auth-Token' => $this->get_token(),
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			/* translators: 1: error message */
			throw new Exception( sprintf( __( 'Delete failed: %1$s', 'backwpup' ), esc_html( $response->get_error_message() ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		$status = wp_remote_retrieve_response_code( $response );

		if ( 204 !== $status && 202 !== $status ) {
			/* translators: 1: error message */
			throw new Exception( sprintf( __( 'Delete failed with status: %1$s', 'backwpup' ), esc_html( $status ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}
	}


	/**
	 * Deleted segments file
	 *
	 * @param string $filename The filename.
	 *
	 * @return void
	 */
	public function delete_segments_file( string $filename ): void {
		$prefix  = $filename . '/';
		$objects = $this->object_list( [ 'prefix' => $prefix ] );

		foreach ( $objects as $object ) {
			$this->delete( $object['name'] );
		}
	}

	/**
	 * Get public url.
	 *
	 * @return string
	 */
	public function get_public_url(): string {
		return $this->cloud_files_endpoint_url;
	}

	/**
	 * Get file size
	 *
	 * @param string $object_name The object name.
	 *
	 * @return int
	 *
	 * @throws Exception When request failed.
	 * @throws RuntimeException Runtime exception.
	 */
	public function get_file_size( string $object_name ): int {
		$container_name = $this->get_container_name();

		$url = "{$this->cloud_files_endpoint_url}/{$container_name}/{$object_name}";
		try {
			$head = wp_remote_head(
				$url,
				[
					'headers' => [ 'X-Auth-Token' => $this->get_token() ],
				]
				);

			if ( is_wp_error( $head ) ) {
				throw new Exception( 'HEAD request failed: ' . $head->get_error_message() );
			}

			$file_size = ( wp_remote_retrieve_header( $head, 'content-length' ) ?: 0 );

		} catch ( Exception $exception ) {
			throw new RuntimeException(
				sprintf(
				// translators: %s: file name.
					esc_html__( 'Could not get file %s from Rackspace', 'backwpup' ),
					esc_html( $object_name )
				)
			);
		}

		return (int) $file_size;
	}
}
