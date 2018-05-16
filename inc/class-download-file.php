<?php

/**
 * Class BackWPup_Download_File
 */
class BackWPup_Download_File implements BackWPup_Download_File_Interface {

	/**
	 * Type
	 *
	 * @var string The mime file type
	 */
	private $type;

	/**
	 * The file path
	 *
	 * @var string The path of the file to download
	 */
	private $filepath;

	/**
	 * File Name
	 *
	 * @var string The file name
	 */
	private $filename;

	/**
	 * @var string
	 *
	 * @string The encoding type
	 */
	private static $encoding = 'binary';

	/**
	 * File content length
	 *
	 * @var int The length of the file
	 */
	private $length;

	/**
	 * Callback
	 *
	 * @var callable The callback to call that will perform the download action
	 */
	private $callback;

	/**
	 * Capability
	 *
	 * @var @string The capability needed to download the file
	 */
	private $capability;

	/**
	 * BackWPup_Download_File constructor
	 *
	 * @todo move the file stuffs into a specific class to manage only files. Blocked by class-file.php
	 *
	 * @throws \InvalidArgumentException In case the callback is not a valid callback.
	 *
	 * @param string   $filepath   The path of the file to download.
	 * @param string   $type       The mime file type.
	 * @param callable $callback   The callback to call that will perform the download action.
	 * @param string   $capability The capability needed to download the file.
	 */
	public function __construct( $filepath, $type, $callback, $capability ) {

		if ( ! is_callable( $callback ) ) {
			throw new \InvalidArgumentException(
				sprintf( 'Invalid callback passed to %s. Callback parameter must be callable.', __CLASS__ )
			);
		}

		$this->type     = $type;
		$this->filepath = $filepath;
		$this->filename = basename( $filepath );
		$this->callback = $callback;

		// Calculate the length of the file.
		$this->length = file_exists( $filepath ) ? filesize( $filepath ) : 0;

		// Set the capability.
		$this->capability = $capability;
	}

	/**
	 * @inheritdoc
	 */
	public function download() {

		if ( ! current_user_can( $this->capability ) ) {
			wp_die( 'Cheating Uh?' );
		}

		$this->check_filename()
		     ->perform_download_callback();
	}

	/**
	 * @inheritdoc
	 */
	public function clean_ob() {

		$level = ob_get_level();
		if ( $level ) {
			for ( $i = 0; $i < $level; $i++ ) {
				ob_end_clean();
			}
		}

		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function filepath() {

		return $this->filepath;
	}

	/**
	 * @inheritdoc
	 */
	public function check_filename() {

		// Sanitize filename, avoid wrong files.
		$filename = backwpup_sanitize_file_name( basename( $this->filename ) );

		// Die if filename contains invalid characters.
		if ( $filename !== $this->filename ) {
			wp_die( esc_html__( 'Invalid file name, seems file include invalid characters.', 'backwpup' ) );
		}

		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function headers() {

		$level = ob_get_level();
		if ( $level ) {
			for ( $i = 0; $i < $level; $i++ ) {
				ob_end_clean();
			}
		}

		// phpcs:ignore
		@set_time_limit( 300 );
		nocache_headers();

		// Set headers.
		header( 'Content-Description: File Transfer' );
		header( "Content-Type: {$this->type}" );
		header( "Content-Disposition: attachment; filename={$this->filename}" );
		header( 'Content-Transfer-Encoding: ' . self::$encoding );
		header( "Content-Length: {$this->length}" );

		return $this;
	}

	/**
	 * Perform the Download
	 *
	 * Note: The callback must call `die` it self.
	 *
	 * @return void
	 */
	private function perform_download_callback() {

		call_user_func( $this->callback, $this );
	}
}
