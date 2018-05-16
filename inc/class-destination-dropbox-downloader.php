<?php
/**
 * Dropbox Files Downloader
 *
 * @package Inpsyde\BackWPup
 */

/**
 * Class BackWPup_Destination_Dropbox_Downloader
 *
 * @since   3.5.0
 * @package Inpsyde\BackWPup
 */
final class BackWPup_Destination_Dropbox_Downloader implements BackWPup_Destination_Downloader_Interface {

	/**
	 * Capability
	 *
	 * @var string The capability the user should have in order to download the file.
	 */
	private static $capability = 'backwpup_backups_download';

	/**
	 * Service
	 *
	 * @since 3.5.0
	 *
	 * @var mixed Depending on the service. It will be an instance of that class
	 */
	private $service;

	/**
	 * Job ID
	 *
	 * @since 3.5.0
	 *
	 * @var int The job Identifier to use to retrieve the job informations
	 */
	private $job_id;

	/**
	 * File Path
	 *
	 * @since 3.5.0
	 *
	 * @var string From where download the file content
	 */
	private $file_path;

	/**
	 * Destination
	 *
	 * @since 3.5.0
	 *
	 * @var string Where store the file content
	 */
	private $destination;

	/**
	 * File handle
	 *
	 * @var resource A handle to the file being downloaded
	 */
	private $file_handle;

	/**
	 * Destructor
	 *
	 * Closes file handle if opened.
	 */
	public function __destruct() {

		if ( $this->file_handle ) {
			fclose( $this->file_handle );
		}
	}

	/**
	 * @inheritdoc
	 */
	public function with_service() {

		$this->service = new \BackWPup_Destination_Dropbox_API(
			\BackWPup_Option::get( $this->job_id, 'dropboxroot' )
		);

		$this->service->setOAuthTokens( \BackWPup_Option::get( $this->job_id, 'dropboxtoken' ) );

		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function download() {

		if ( ! current_user_can( self::$capability ) ) {
			wp_die( 'Cheatin&#8217; huh?' );
		}

		try {
			backwpup_wpfilesystem()->put_contents(
				$this->destination,
				$this->service->download( array( 'path' => $this->file_path ) )
			);
		} catch ( \Exception $e ) {
			BackWPup_Admin::message( 'Dropbox: ' . $e->getMessage() );
		}

		if ( ! is_file( $this->destination ) ) {
			throw new \BackWPup_Destination_Download_Exception();
		}

		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function downloadChunk( $startByte, $endByte ) {

		if ( ! current_user_can( self::$capability ) ) {
			wp_die( 'Cheatin&#8217; huh?' );
		}

		try {
			if ( is_null( $this->file_handle ) ) {
				// Open file; write mode if $startByte is 0, else append
				$this->file_handle = fopen( $this->destination, $startByte == 0 ? 'wb' : 'ab' );

				if ( $this->file_handle === false ) {
					throw new \RuntimeException( __( 'File could not be opened for writing.', 'backwpup' ) );
				}
			}

			$data = $this->service->download(
				array( 'path' => $this->file_path ),
				$startByte,
				$endByte
			);

			$bytes = fwrite( $this->file_handle, $data );
			if ( $bytes === false ) {
				throw new \RuntimeException( __( 'Could not write data to file.', 'backwpup' ) );
			}
		} catch ( \Exception $e ) {
			BackWPup_Admin::message( 'Dropbox: ' . $e->getMessage() );
		}
	}

	/**
	 * @inheritdoc
	 */
	public function for_job( $job_id ) {

		$this->job_id = $job_id;

		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function from( $file_path ) {

		$this->file_path = $file_path;

		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function to( $destination ) {

		$this->destination = $destination;

		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function getSize() {

		$metadata = $this->service->filesGetMetadata( array( 'path' => $this->file_path ) );

		return $metadata['size'];
	}
}
