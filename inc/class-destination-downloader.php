<?php
/**
 * BackWPup_Destination_Downloader.
 *
 * @since   3.6.0
 */

use Inpsyde\Restore\Api\Controller\DecryptController;
use Inpsyde\Restore\Api\Module\Decryption\Exception\DecryptException;

use function Inpsyde\BackWPup\Infrastructure\Restore\restore_container;

/**
 * Class BackWPup_Destination_Downloader.
 *
 * @since   3.6.0
 */
class BackWPup_Destination_Downloader {
	public const CAPABILITY = 'backwpup_backups_download';

	public const STATE_DOWNLOADING = 'downloading';
	public const STATE_ERROR       = 'error';
	public const STATE_DONE        = 'done';

	/**
	 * Downloader data object.
	 *
	 * @var \BackWpUp_Destination_Downloader_Data
	 */
	private $data;

	/**
	 * Destination downloader service.
	 *
	 * @var \BackWPup_Destination_Downloader_Interface
	 */
	private $destination;

	/**
	 * BackWPup_Downloader constructor.
	 *
	 * @param \BackWpUp_Destination_Downloader_Data      $data        Download data.
	 * @param \BackWPup_Destination_Downloader_Interface $destination Destination service.
	 */
	public function __construct(
		BackWpUp_Destination_Downloader_Data $data,
		BackWPup_Destination_Downloader_Interface $destination
	) {
		$this->data        = $data;
		$this->destination = $destination;
	}

	/**
	 * Download file via ajax.
	 *
	 * @return void
	 */
	public static function download_by_ajax() {
		$dest = (string) filter_input( INPUT_GET, 'destination' );
		if ( ! $dest ) {
			return;
		}

		$job_id = (int) filter_input( INPUT_GET, 'jobid', FILTER_SANITIZE_NUMBER_INT );
		if ( ! $job_id ) {
			return;
		}

		$file       = (string) filter_input( INPUT_GET, 'file' );
		$file_local = (string) filter_input( INPUT_GET, 'local_file' );
		if ( ! $file || ! $file_local ) {
			return;
		}

		set_time_limit( 0 );
		// Set up eventsource headers.
		header( 'Content-Type: text/event-stream' );
		header( 'Cache-Control: no-cache' );
		header( 'X-Accel-Buffering: no' );
		header( 'Content-Encoding: none' );

		// 2KB padding for IE.
		echo esc_html( ':' . str_repeat( ' ', 2048 ) . "\n\n" );
		// Never let the browser reconnect on its own: a reconnect restarts the whole copy and truncates the local file.
		echo esc_html( "retry: 86400000\n\n" );

		// Ensure we're not buffered.
		wp_ob_end_flush_all();
		flush();

		/**
		 * Destination handler.
		 *
		 * @var \BackWPup_Destinations $dest_class
		 */
		$dest_class = BackWPup::get_destination( $dest );
		$dest_class->file_download(
			$job_id,
			trim( sanitize_text_field( $file ) ),
			trim( sanitize_text_field( $file_local ) )
		);
	}

	/**
	 * Downloads the file by chunks.
	 *
	 * @param string $final_file_path Optional. When given, the chunks are written to the local file path of the
	 *                                data object (a temporary file) which is renamed to this path once complete,
	 *                                so a partially copied file is never served.
	 *
	 * @throws DecryptException When an encrypted archive needs a decryption key.
	 *
	 * @return bool True on success, false on error.
	 */
	public function download_by_chunks( string $final_file_path = '' ) {
		$this->ensure_user_can_download();

		$source_file_path = $this->data->source_file_path();
		$local_file_path  = $this->data->local_file_path();
		$start_byte       = 0;
		$chunk_size       = 2 * 1024 * 1024;
		$end_byte         = $start_byte + $chunk_size - 1;

		try {
			// Inside the try block, so a missing file or a connection error is reported to the user.
			$size = (int) $this->destination->calculate_size();
			if ( $size <= 0 ) {
				throw new \RuntimeException( esc_html__( 'The backup file could not be found or is empty.', 'backwpup' ) );
			}

			if ( $end_byte >= $size ) {
				$end_byte = $size - 1;
			}

			while ( $end_byte <= $size ) {
				$this->destination->download_chunk( $start_byte, $end_byte );
				self::send_message(
					[
						'state'            => self::STATE_DOWNLOADING,
						'start_byte'       => $start_byte,
						'end_byte'         => $end_byte,
						'size'             => $size,
						'download_percent' => round( ( $end_byte + 1 ) / $size * 100 ),
						'filename'         => basename( $source_file_path ),
					]
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

			if ( '' !== $final_file_path && $final_file_path !== $local_file_path ) {
				// Release the destination so its file handles are closed before renaming.
				$this->destination = null;
				clearstatcache( true, $local_file_path );
				if ( (int) filesize( $local_file_path ) !== $size ) {
					throw new \RuntimeException( esc_html__( 'The downloaded file is incomplete. Please try again.', 'backwpup' ) );
				}
				if ( file_exists( $final_file_path ) ) {
					wp_delete_file( $final_file_path );
				}
				if ( ! rename( $local_file_path, $final_file_path ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename
					throw new \RuntimeException( esc_html__( 'Could not move the downloaded file into place.', 'backwpup' ) );
				}
				$local_file_path = $final_file_path;
			}

			if ( BackWPup::is_pro() ) {
				/**
				 * Decryption service.
				 *
				 * @var \Inpsyde\Restore\Api\Module\Decryption\Decrypter $decrypter
				 */
				$decrypter = restore_container( 'decrypter' );
				if ( $decrypter->isEncrypted( $local_file_path ) ) {
					throw new DecryptException( DecryptController::STATE_NEED_DECRYPTION_KEY );
				}
			}
		} catch ( Exception $e ) {
			self::send_message(
				[
					'state'   => self::STATE_ERROR,
					'message' => $e->getMessage(),
				],
				'log'
			);

			return false;
		}

		self::send_message(
			[
				'state'   => self::STATE_DONE,
				'message' => esc_html__( 'Your download is being generated &hellip;', 'backwpup' ),
			]
			);

		return true;
	}

	/**
	 * Downloads a file in chunks using WP-CLI.
	 *
	 * The method calculates the file size, divides it into chunks, and downloads each chunk
	 * sequentially until the entire file is downloaded.
	 *
	 * @return void
	 */
	public function download_by_chunks_wp_cli() {

		$size       = $this->destination->calculate_size();
		$start_byte = 0;
		$chunk_size = 2 * 1024 * 1024;
		$end_byte   = $start_byte + $chunk_size - 1;

		if ( $end_byte >= $size ) {
			$end_byte = $size - 1;
		}

		/* translators: %s: file name */
		$progress = \WP_CLI\Utils\make_progress_bar( sprintf( __( 'Download %s:', 'backwpup' ), basename( $this->data->source_file_path() ) ), $size );
		try {
			while ( $end_byte <= $size ) {
				$this->destination->download_chunk( $start_byte, $end_byte );
				$progress->tick( $chunk_size );

				if ( $end_byte === $size - 1 ) {
					$progress->finish();
					break;
				}

				$start_byte = $end_byte + 1;
				$end_byte   = $start_byte + $chunk_size - 1;

				if ( $start_byte < $size && $end_byte >= $size ) {
					$end_byte = $size - 1;
				}
			}
		} catch ( \Exception $e ) {
			/* translators: %s: error message */
			\WP_CLI::error( sprintf( __( 'Backup file download error: %s.', 'backwpup' ), $e->getMessage() ) );
		}
	}

	/**
	 * Ensure user capability.
	 *
	 * @return void
	 */
	private function ensure_user_can_download() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die();
		}
	}

	/**
	 * Send event source message.
	 *
	 * @param array  $data  Message data.
	 * @param string $event Event name.
	 *
	 * @return void
	 */
	private static function send_message( $data, $event = 'message' ) {
		echo 'event: ' . esc_html( $event ) . "\n";
		echo 'data: ' . wp_json_encode( $data ) . "\n\n";
		flush();
	}
}
