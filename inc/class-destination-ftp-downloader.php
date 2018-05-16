<?php
/**
 * BackWPup_Destination_Ftp_Downloader
 *
 * @since   3.5.0
 * @package Inpsyde\BackWPup
 */

/**
 * Class BackWPup_Destination_Ftp_Downloader
 *
 * @since   3.5.0
 * @package Inpsyde\BackWPup
 */
final class BackWPup_Destination_Ftp_Downloader implements BackWPup_Destination_Downloader_Interface {

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
		 * FTP handle
		 *
		 * @var resource The handle to the FTP file.
		 */
	private $ftp_handle;
	
	/**
		 * Close the file handle on destruct
		 */
	public function __destruct() {

		if ( $this->file_handle ) {
			fclose( $this->file_handle );
		}
		
		if ( $this->ftp_handle ) {
			fclose( $this->ftp_handle );
		}
	}
	
	/**
	 * @inheritdoc
	 */
	public function with_service() {

		$opts = (object) BackWPup_Option::get_job( $this->job_id );

		$this->service = new BackWPup_Destination_Ftp_Connect(
			$opts->ftphost,
			$opts->ftpuser,
			BackWPup_Encryption::decrypt( $opts->ftppass ),
			$opts->ftphostport,
			$opts->ftptimeout,
			$opts->ftpssl,
			$opts->ftppasv
		);

		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function download() {

		if ( ! current_user_can( self::$capability ) ) {
			wp_die( 'Cheatin&#8217; huh?' );
		}

		// Connect and Login
		$resource = $this->service
			->connect()
			->resource();

		// Download the file and close connection.
		ftp_get( $resource, $this->destination, $this->file_path, FTP_BINARY );
		ftp_close( $resource );

		if ( ! is_file( $this->destination ) ) {
			throw new \BackWPup_Destination_Download_Exception(
				'Something went wrong during file download, seems wasn\'t possible to store it. Please see the log.'
			);
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

			if ( ! $this->ftp_handle ) {
				// Construct FTP file URL
				$ctx = stream_context_create(array( 'ftp' => array( 'resume_pos' => $startByte ) ) );
				$url = $this->service->getURL( $this->file_path, false, $ctx );
				
				$this->ftp_handle = fopen( $url, 'r' );
				if ( $this->ftp_handle === false ) {
					throw new \RuntimeException( __( 'Cannot open FTP file for download.', 'backwpup' ) );
				}
			}
			
			if ( ! $this->file_handle ) {
				$this->file_handle = fopen( $this->destination, $startByte == 0 ? 'wb' : 'ab' );
				
				if ( $this->file_handle === false ) {
					throw new \RuntimeException( __( 'File could not be opened for writing.', 'backwpup' ) );
				}
			}
			
			$bytes = stream_copy_to_stream(
				$this->ftp_handle,
				$this->file_handle,
				$endByte - $startByte + 1,
				0
			);
			if ( $bytes === false ) {
				throw new \RuntimeException( __( 'Could not write data to file.', 'backwpup' ) );
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

		$resource = $this->service
		->connect()
		->resource();
		
		$size = ftp_size( $resource, $this->file_path );
		ftp_close( $resource );
		
		return $size;
	}
}
