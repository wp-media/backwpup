<?php
declare(strict_types=1);

use WPMedia\BackWPup\StorageProviders\Rackspace\RackspaceProvider as Rackspace;

/**
 * RCS Downloader.
 *
 * @since 5.5
 */
class BackWPup_Destination_RSC_Downloader implements BackWPup_Destination_Downloader_Interface {

	/**
	 * Local handle.
	 *
	 * @var resource
	 */
	private $local_handle;

	/**
	 * BackWpUp_Destination_Downloader_Data Instance,
	 *
	 * @var BackWpUp_Destination_Downloader_Data
	 */
	private BackWpUp_Destination_Downloader_Data $data;

	/**
	 * Rackspace instance.
	 *
	 * @var Rackspace
	 */
	private Rackspace $rackspace;

	/**
	 * BackWPup_Destination_RSC_Downloader constructor.
	 *
	 * @param BackWpUp_Destination_Downloader_Data $data Destination downloader instance.
	 */
	public function __construct( BackWpUp_Destination_Downloader_Data $data ) {
		$this->data      = $data;
		$this->rackspace = $this->initialise();
	}

	/**
	 * Destruct, clean up stuff after download is complete.
	 */
	public function __destruct() {
		fclose( $this->local_handle ); // phpcs:ignore
	}

	/**
	 * Download part of the backup file.
	 *
	 * @param int $start_byte
	 * @param int $end_byte
	 *
	 * @throws Exception In case something went wrong.
	 * @throws RuntimeException Throw runtime error if we can't write to file.
	 */
	public function download_chunk( $start_byte, $end_byte ) {
		$object_name    = $this->data->source_file_path();
		$container_name = $this->rackspace->get_container_name();

		$url  = "{$this->rackspace->get_public_url()}/{$container_name}/{$object_name}";
		$args = [
			'timeout' => 300,
			'headers' => [
				'X-Auth-Token' => $this->rackspace->get_token(),
				'Range'        => 'bytes=' . $start_byte . '-' . $end_byte,
			],
		];

		try {
			$this->open_local_handle( $start_byte );

			$response = wp_remote_get( $url, $args );

			if ( is_wp_error( $response ) ) {
				throw new Exception( __( 'Chunk download failed: ', 'backwpup' ) . $response->get_error_message() );
			}

			$response_code = wp_remote_retrieve_response_code( $response );
			if ( 206 !== $response_code && 200 !== $response_code ) {
				throw new Exception( __( 'Chunk download failed with status: ', 'backwpup' ) . $response_code );
			}

			$body           = wp_remote_retrieve_body( $response );
			$content_length = wp_remote_retrieve_header( $response, 'content-length' );

			if ( empty( $body ) || '0' === $content_length ) {
				throw new Exception( __( 'Could not read chunk data. Empty response.', 'backwpup' ) );
			}

			// Write to local file.
			$bytes = fwrite( $this->local_handle, $body ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
			if ( 0 === $bytes || false === $bytes ) {
				throw new RuntimeException( __( 'Could not write data to file.', 'backwpup' ) );
			}
		} catch ( Exception $e ) {
			throw new Exception( esc_html__( 'Download failed.', 'backwpup' ) );
		}
	}

	/**
	 * Handle the local writing of the download process.
	 *
	 * @param int $start_byte Start byte.
	 *
	 * @throws RuntimeException If file could not be opened.
	 * @return void
	 */
	public function open_local_handle( int $start_byte ): void {
		if ( is_resource( $this->local_handle ) ) {
			return;
		}

		$mode               = 0 === $start_byte ? 'wb' : 'ab';
		$this->local_handle = fopen( $this->data->local_file_path(), $mode ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen

		if ( ! is_resource( $this->local_handle ) ) {
			throw new RuntimeException( esc_html__( 'File could not be opened for writing.', 'backwpup' ) );
		}
	}

	/**
	 * Calculate the size of download.
	 *
	 * @return int
	 * @throws Exception If getting the file size failed.
	 */
	public function calculate_size(): int {
		return $this->rackspace->get_file_size( $this->data->source_file_path() );
	}

	/**
	 * Initialise rackspace cloud
	 *
	 * @return Rackspace
	 * @throws Exception Throw an error if request fails.
	 */
	private function initialise(): Rackspace {
		$storage_provider = new Rackspace(
			[
				'username'       => BackWPup_Option::get( $this->data->job_id(), 'rscusername' ),
				'api_key'        => BackWPup_Encryption::decrypt( BackWPup_Option::get( $this->data->job_id(), 'rscapikey' ) ),
				'container_name' => BackWPup_Option::get( $this->data->job_id(), 'rsccontainer' ),
				'region'         => BackWPup_Option::get( $this->data->job_id(), 'rscregion' ),
			],
		);

		$storage_provider->initialise();

		return $storage_provider;
	}
}
