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
final class BackWPup_Destination_Ftp_Downloader extends BackWPup_Destination_Downloader {

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
	public function getSize() {

		$resource = $this->service
		->connect()
		->resource();
		
		$size = ftp_size( $resource, $this->file_path );
		ftp_close( $resource );
		
		return $size;
	}
}
