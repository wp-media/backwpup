<?php
/**
 * Folder Downloader
 *
 * @package Inpsyde\BackWPup
 */

/**
 * Class BackWPup_Destination_Folder_Downloader
 *
 * @since   3.6.0
 * @package Inpsyde\BackWPup
 */
final class BackWPup_Destination_Folder_Downloader extends BackWPup_Destination_Downloader {

	/**
	 * File handle
	 *
	 * @var resource A handle to the file being downloaded
	 */
	private $file;

	/**
	 * Destructor
	 *
	 * Closes file handle if opened.
	 */
	public function __destruct() {

		if ( $this->file ) {
			$this->file = null;
		}
	}

	/**
	 * @inheritdoc
	 */
	public function with_service() {

		$backup_dir = esc_attr( BackWPup_Option::get( $this->job_id, 'backupdir' ) );
		$backup_dir = BackWPup_File::get_absolute_path( $backup_dir );
		$file       = realpath( BackWPup_Sanitize_Path::sanitize_path(
			trailingslashit( $backup_dir ) . basename( $this->file_path ) )
		);

		try {
			$this->service = new \SplFileObject( $file, 'rb' );
		} catch ( \RuntimeException $e ) {
			throw new \RuntimeException( __( 'File could not be opened for reading.', 'backwpup' ) );
		}

		try {
			$this->file = new \SplFileObject( $this->destination, 'wb' );
		} catch ( \RuntimeException $e ) {
			throw new \RuntimeException( __( 'File could not be opened for writing.', 'backwpup' ) );
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

		if ( $this->service->ftell() != $startByte ) {
			$this->service->fseek( $startByte );
		}

		try {
			$data = $this->service->fread( $endByte - $startByte + 1 );

			$bytes = $this->file->fwrite( $data );
			if ( $bytes == 0 ) {
				throw new \RuntimeException( __( 'Could not write data to file.', 'backwpup' ) );
			}
		} catch ( \Exception $e ) {
			BackWPup_Admin::message( 'Folder: ' . $e->getMessage() );
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

		return $this->service->getSize();
	}

}
