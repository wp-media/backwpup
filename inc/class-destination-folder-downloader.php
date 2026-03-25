<?php
/**
 * Folder Downloader.
 */

/**
 * Class BackWPup_Destination_Folder_Downloader.
 *
 * @since   3.6.0
 */
final class BackWPup_Destination_Folder_Downloader implements BackWPup_Destination_Downloader_Interface {

	public const OPTION_BACKUP_DIR = 'backupdir';

	/**
	 * Downloader data object.
	 *
	 * @var \BackWpUp_Destination_Downloader_Data
	 */
	private $data;

	/**
	 * Source file handler.
	 *
	 * @var resource
	 */
	private $source_file_handler;

	/**
	 * Local file handler.
	 *
	 * @var resource
	 */
	private $local_file_handler;

	/**
	 * BackWPup_Destination_Folder_Downloader constructor.
	 *
	 * @param \BackWpUp_Destination_Downloader_Data $data Download data.
	 */
	public function __construct( BackWpUp_Destination_Downloader_Data $data ) {
		$this->data = $data;

		$this->source_file_handler();
		$this->local_file_handler();
	}

	/**
	 * Clean up things.
	 */
	public function __destruct() {
		fclose( $this->local_file_handler ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $this->source_file_handler ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param int $start_byte Start byte offset.
	 * @param int $end_byte   End byte offset.
	 *
	 * @throws Exception When file operations fail.
	 */
	public function download_chunk( $start_byte, $end_byte ) {
		if ( ftell( $this->source_file_handler ) !== $start_byte ) {
			fseek( $this->source_file_handler, $start_byte );
		}

		$data = fread( $this->source_file_handler, $end_byte - $start_byte + 1 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
		if ( false === $data ) {
			throw new Exception( esc_html__( 'Could not read data from source file.', 'backwpup' ) );
		}

		$bytes = (int) fwrite( $this->local_file_handler, $data ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
		if ( 0 === $bytes ) {
			throw new Exception( esc_html__( 'Could not write data into target file.', 'backwpup' ) );
		}
	}

	/**
	 * Calculate the file size.
	 *
	 * @return int
	 */
	public function calculate_size() {
		return filesize( $this->source_backup_file() );
	}

	/**
	 * Retrieve the file handler for the source file.
	 *
	 * @return void
	 * @throws \RuntimeException When the source file cannot be opened.
	 */
	private function source_file_handler() {
		if ( is_resource( $this->source_file_handler ) ) {
			return;
		}

		$file = $this->source_backup_file();

		$this->source_file_handler = @fopen( $file, 'rb' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( ! is_resource( $this->source_file_handler ) ) {
			throw new \RuntimeException( esc_html__( 'File could not be opened for reading.', 'backwpup' ) );
		}
	}

	/**
	 * Get the backup directory.
	 *
	 * @return string
	 */
	private function backup_dir() {
		$backup_dir = esc_attr( BackWPup_Option::get( $this->data->job_id(), self::OPTION_BACKUP_DIR ) );
		$backup_dir = trailingslashit( BackWPup_File::get_absolute_path( $backup_dir ) );

		return (string) $backup_dir;
	}

	/**
	 * Get the backup file path.
	 *
	 * @return string
	 */
	private function source_backup_file() {
		return (string) realpath(
			BackWPup_Sanitize_Path::sanitize_path(
				$this->backup_dir() . basename( $this->data->source_file_path() )
			)
		);
	}

	/**
	 * Retrieve the file handler for the local file.
	 *
	 * @return void
	 * @throws \RuntimeException When the local file cannot be opened.
	 */
	private function local_file_handler() {
		if ( is_resource( $this->local_file_handler ) ) {
			return;
		}

		try {
			$this->local_file_handler = @fopen( $this->data->local_file_path(), 'wb' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		} catch ( \RuntimeException $exc ) {
			throw new \RuntimeException( esc_html__( 'File could not be opened for writing.', 'backwpup' ) );
		} catch ( \LogicException $exc ) {
			throw new \RuntimeException(
				sprintf(
					// translators: $1 is the path of the local file where the backup will be stored.
					esc_html__( '%s is a directory not a file.', 'backwpup' ),
					esc_html( $this->data->local_file_path() )
				)
			);
		}
	}
}
