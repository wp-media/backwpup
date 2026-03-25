<?php
/**
 * SugarSync Files Downloader.
 */

/**
 * Class BackWPup_Destination_SugarSync_Downloader.
 */
class BackWPup_Destination_SugarSync_Downloader implements BackWPup_Destination_Downloader_Interface {

	/**
	 * Data.
	 * 1. job_id
	 * 2. source_file_path
	 * 3. local_file_path
	 *
	 * @var \BackWpUp_Destination_Downloader_Data
	 */
	private $data;

	/**
	 * Local file handler.
	 *
	 * @var resource
	 */
	private $local_file_handler;

	/**
	 * SugarSync API.
	 *
	 * @var BackWPup_Destination_SugarSync_API
	 */
	private $sugarsync_api;

	/**
	 * BackWPup_Destination_SugarSync_Downloader constructor.
	 *
	 * @param \BackWpUp_Destination_Downloader_Data $data
	 */
	public function __construct( BackWpUp_Destination_Downloader_Data $data ) {
		$this->data = $data;

		$this->sugarsync_api = new BackWPup_Destination_SugarSync_API(
			\BackWPup_Option::get( $this->data->job_id(), 'sugarrefreshtoken' )
		);
	}

	/**
	 * Clean up things.
	 */
	public function __destruct() {
		fclose( $this->local_file_handler ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param int $start_byte start byte.
	 * @param int $end_byte end byte.
	 *
	 * @throws \RuntimeException In case something went wrong.
	 */
	public function download_chunk( $start_byte, $end_byte ): void {
		$this->local_file_handler( $start_byte );

		try {
			$data = $this->sugarsync_api->download_chunk(
				$this->data->source_file_path(),
				$start_byte,
				$end_byte
			);

			$bytes = (int) fwrite( $this->local_file_handler, (string) $data ); //phpcs:ignore
			if ( 0 === $bytes ) {
				throw new \RuntimeException( esc_html__( 'Could not write data to file.', 'backwpup' ) );
			}
		} catch ( \Exception $e ) {
			BackWPup_Admin::message( 'SugarSync: ' . $e->getMessage() );
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function calculate_size(): int {
		$metadata = $this->sugarsync_api->get( $this->data->source_file_path() );

		return (int) $metadata->size;
	}

	/**
	 * Set local file handler.
	 *
	 * @param int $start_byte
	 *
	 * @throws \RuntimeException In case something went wrong.
	 */
	private function local_file_handler( $start_byte ) {
		if ( is_resource( $this->local_file_handler ) ) {
			return;
		}

		// Open file; write mode if $start_byte is 0, else append.
		$this->local_file_handler = fopen( $this->data->local_file_path(), 0 === $start_byte ? 'wb' : 'ab' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen

		if ( ! is_resource( $this->local_file_handler ) ) {
			throw new \RuntimeException( esc_html__( 'File could not be opened for writing.', 'backwpup' ) );
		}
	}
}
