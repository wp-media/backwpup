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
class BackWPup_Destination_Downloader {

	const ARCHIVE_ENCRYPT_OPTION = 'archiveencryption';
	const CAPABILITY = 'backwpup_backups_download';

	/**
	 * @var \BackWpUp_Destination_Downloader_Data
	 */
	private $data;

	/**
	 * @var \BackWPup_Destination_Downloader_Interface
	 */
	private $destination;

	/**
	 * BackWPup_Downloader constructor
	 *
	 * @param \BackWpUp_Destination_Downloader_Data      $data
	 * @param \BackWPup_Destination_Downloader_Interface $destination
	 */
	public function __construct(
		BackWpUp_Destination_Downloader_Data $data,
		BackWPup_Destination_Downloader_Interface $destination
	) {

		$this->data        = $data;
		$this->destination = $destination;
	}

	/**
	 * @return bool
	 */
	public function download_by_chunks() {

		$this->ensure_user_can_download();

		$decripted            = false;
		$need_to_be_decripted = BackWPup_Option::get( $this->data->job_id(), self::ARCHIVE_ENCRYPT_OPTION );

		$source_file_path = $this->data->source_file_path();
		$local_file_path  = $this->data->local_file_path();

		$size       = $this->destination->calculate_size();
		$start_byte = 0;
		$chunk_size = 2 * 1024 * 1024;
		$end_byte   = $start_byte + $chunk_size - 1;

		if ( $end_byte >= $size ) {
			$end_byte = $size - 1;
		}

		try {
			while ( $end_byte <= $size ) {
				$this->destination->download_chunk( $start_byte, $end_byte );
				self::send_message(
					array(
						'state'            => 'downloading',
						'start_byte'       => $start_byte,
						'end_byte'         => $end_byte,
						'size'             => $size,
						'download_percent' => round( ( $end_byte + 1 ) / $size * 100 ),
						'filename'         => basename( $source_file_path ),
					)
				);

				if ( $end_byte === $size - 1 ) {
					break;
				}

				$start_byte = $end_byte + 1;
				$end_byte   = $start_byte + $chunk_size - 1;

				if ( $start_byte < $size && $end_byte >= $size ) {
					$end_byte = $size - 1;
				}
			}

			// Decrypt
			if ( $need_to_be_decripted ) {
				$decrypter = new BackWPup_Decrypter( $local_file_path );
				$decripted = $decrypter->decrypt();
			}

			if ( ! $decripted && $need_to_be_decripted ) {
				throw new \BackWPup_Destination_Download_Exception();
			}

			self::send_message( array(
				'state' => 'done',
			) );
		} catch ( \Exception $e ) {
			self::send_message(
				array(
					'state'   => 'error',
					'message' => $e->getMessage(),
				),
				'log'
			);

			return false;
		}

		return true;
	}

	/**
	 * Ensure user capability
	 */
	private function ensure_user_can_download() {

		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die();
		}
	}

	/**
	 * @param        $data
	 * @param string $event
	 */
	private static function send_message( $data, $event = 'message' ) {

		echo "event: {$event}\n";
		echo "data: " . wp_json_encode( $data ) . "\n\n";
		flush();
	}
}
