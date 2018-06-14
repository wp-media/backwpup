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
final class BackWPup_Destination_Dropbox_Downloader extends BackWPup_Destination_Downloader {

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
	public function getSize() {

		$metadata = $this->service->filesGetMetadata( array( 'path' => $this->file_path ) );

		return $metadata['size'];
	}

}
