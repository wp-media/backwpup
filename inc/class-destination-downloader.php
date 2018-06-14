<?php
/**
 * BackWPup_Destination_Downloader
 *
 * @since   3.6.0
 * @package Inpsyde\BackWPup
 */

/**
 * Class BackWPup_Destination_Downloader
 *
 * @since   3.6.0
 * @package Inpsyde\BackWPup
 */
class  BackWPup_Destination_Downloader {

	/**
	 * Capability
	 *
	 * @var string The capability the user should have in order to download the file.
	 */
	protected static $capability = 'backwpup_backups_download';

	/**
	 * Service
	 *
	 * @since 3.5.0
	 *
	 * @var mixed Depending on the service. It will be an instance of that class
	 */
	protected $service;

	/**
	 * Job ID
	 *
	 * @since 3.5.0
	 *
	 * @var int The job Identifier to use to retrieve the job informations
	 */
	protected $job_id;

	/**
	 * File Path
	 *
	 * @since 3.5.0
	 *
	 * @var string From where download the file content
	 */
	protected $file_path;

	/**
	 * Destination
	 *
	 * @since 3.5.0
	 *
	 * @var string Where store the file content
	 */
	protected $destination;

	/**
	 * Download the File
	 *
	 * @since 3.5.0
	 *
	 * @uses wp_die In case the user has not the correct permissions to download the file.
	 *
	 * @throws \BackWPup_Destination_Download_Exception In case the file has not be stored correctly in the folder.
	 *
	 * @return BackWPup_Destination_Downloader The instance for concatenation
	 */
	public function download() {

		if ( ! current_user_can( self::$capability ) ) {
			wp_die( 'Cheatin&#8217; huh?' );
		}

		try {
			$size = $this->getSize();
			$start_byte = 0;
			$chunk_size = 2 * 1024 * 1024;
			$end_byte = $start_byte + $chunk_size - 1;
			if ( $end_byte >= $size ) {
				$end_byte = $size - 1;
			}

			while ( $end_byte <= $size ) {
				$this->downloadChunk( $start_byte, $end_byte );
				self::sendMessage( array(
					'state'            => 'downloading',
					'start_byte'       => $start_byte,
					'end_byte'         => $end_byte,
					'size'             => $size,
					'download_percent' => round( ( $end_byte + 1 ) / $size * 100 ),
					'filename'         => basename( $this->destination ),
				) );
				if ( $end_byte == $size - 1 ) {
					break;
				}
				$start_byte = $end_byte + 1;
				$end_byte = $start_byte + $chunk_size - 1;
				if ( $start_byte < $size && $end_byte >= $size ) {
					$end_byte = $size - 1;
				}
			}

			// Decrypt
			if ( BackWPup_Option::get( $this->job_id, 'archiveencryption' ) ) {
				$decrypter = new BackWPup_Decrypter( $this->destination );
				$decrypter->decrypt();
			}

			self::sendMessage( array(
				'state' => 'done',
			) );
			echo str_repeat( "\n", 4096 );
			flush();
		} catch ( \Exception $e ) {
			BackWPup_Admin::message( $e->getMessage() );
		}

		if ( ! is_file( $this->destination ) ) {
			throw new \BackWPup_Destination_Download_Exception();
		}

		return $this;
	}

	/**
		 * Download file in chunks
		 *
		 * Given a range of bytes, download that chunk of the file from the destination.
		 *
		 * @param int $startByte The start byte of the range
		 * @param int $endByte   The end byte of the range
		 */
	public function downloadChunk( $startByte, $endByte ) {}

	/**
	 * Job ID Setter
	 *
	 * @since 3.5.0
	 *
	 * @param int $job_id The Job Identifier.
	 *
	 * @return BackWPup_Destination_Downloader The instance for concatenation
	 */
	public function for_job( $job_id ) {

		$this->job_id = $job_id;

		return $this;
	}

	/**
	 * From where Download the File
	 *
	 * @since 3.5.0
	 *
	 * @param string $file_path The path/uri of the file to download.
	 *
	 * @return BackWPup_Destination_Downloader The instance for concatenation
	 */
	public function from( $file_path ) {

		$this->file_path = $file_path;

		return $this;
	}

	/**
	 * Local Destination where Store the File
	 *
	 * @since 3.5.0
	 *
	 * @param string $destination The path where store the file content.
	 *
	 * @return BackWPup_Destination_Downloader The instance for concatenation
	 */
	public function to( $destination ) {

		$this->destination = $destination;

		return $this;
	}

	/**
	 * Set and Initialize the Service
	 *
	 * We create the instance of the service and setup it to able to download the content file.
	 * The service depends on the destination used.
	 *
	 * @since 3.5.0
	 *
	 * @return BackWPup_Destination_Downloader The instance for concatenation
	 */
	public function with_service() {}

	/**
		 * Get the size of the destination file.
		 *
		 * @return int The size of the file.
		 */
	public function getSize() {}

	/**
	 * Send Message
	 *
	 * Send EventSource message back to client.
	 *
	 * @param mixed  $data  The data to send back
	 * @param string $event The type of event
	 */
	public static function sendMessage( $data, $event = 'message' ) {

		echo "event: $event\n";
		echo "data: " . wp_json_encode( $data ) . "\n\n";
		// Send 4096 bytes to force PHP to flush
		echo str_repeat( "\n", 4096 );
		flush();
	}

}
