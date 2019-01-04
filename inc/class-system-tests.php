<?php

/**
 * Class BackWPup_System_Testing
 */
class BackWPup_System_Tests {

	/**
	 * @var BackWPup_System_Requirements
	 */
	private $requirements;

	/**
	 * BackWPup_System_Testing constructor
	 *
	 * @param BackWPup_System_Requirements $requirements The instance of the class.
	 */
	public function __construct( BackWPup_System_Requirements $requirements ) {

		$this->requirements = $requirements;
	}

	/**
	 * Is WordPress compatible
	 *
	 * @uses version_compare() To compare the versions
	 *
	 * @return bool True if compatible, false otherwise
	 */
	public function is_wp_version_compatible() {

		return version_compare(
			BackWPup::get_plugin_data( 'wp_version' ),
			$this->requirements->wp_minimum_version(),
			'>='
		);
	}

	/**
	 * Is PHP Compatible
	 *
	 * @uses version_compare() To compare the versions
	 *
	 * @return bool True if compatible, false otherwise
	 */
	public function is_php_version_compatible() {

		return version_compare(
			PHP_VERSION,
			$this->requirements->php_minimum_version(),
			'>='
		);
	}

	/**
	 * Is MySQL Compatible
	 *
	 * @uses version_compare() To compare the versions
	 *
	 * @return bool True if compatible, false otherwise
	 */
	public function is_database_compatible() {

		$version = backwpup_wpdb()->db_version();

		return (
			class_exists( 'mysqli' )
			&& version_compare( $version, $this->requirements->mysql_minimum_version(), '>=' )
		);
	}

	/**
	 * Test if CURL is supported
	 *
	 * @return bool True if supported, false otherwise
	 */
	public function test_curl_init() {

		return function_exists( 'curl_init' );
	}

	/**
	 * Test if ZipArchive is supported
	 *
	 * @return bool True if supported, false otherwise
	 */
	public function test_zip_archive() {

		return class_exists( 'ZipArchive' );
	}

	/**
	 * Test if GZIP is supported
	 *
	 * @return bool True if supported, false otherwise
	 */
	public function support_gzip() {

		return function_exists( 'gzopen' );
	}

	/**
	 * Check if save mode is active
	 *
	 * @return bool Return true if active, false otherwise
	 */
	public function is_save_mode_activated() {

		// @todo `safe_mode` to remove when support for php5.3 will be dropped. For php5.3 the use of `safe_mode` emit a `E_DEPRECATED` but in php5.4 an `E_CORE_ERROR`.
		// phpcs:ignore
		return (bool) ini_get( 'safe_mode' );
	}

	/**
	 * Test if FTP is supported
	 *
	 * @return bool True if supported, false otherwise
	 */
	public function is_ftp_supported() {

		return function_exists( 'ftp_login' );
	}

	/**
	 * Check if Temp dir is writable
	 *
	 * @return string Empty string if everything is ok, a message containing the error otherwise.
	 */
	public function temp_dir_state() {

		return BackWPup_File::check_folder( BackWPup::get_plugin_data( 'TEMP' ), true );
	}

	/**
	 * Check if Log folder dir is writable
	 *
	 * @return string Empty string if it is everything ok, a message containing the error otherwise
	 */
	public function log_folder_state() {

		$log_folder_message = BackWPup_File::check_folder(
			BackWPup_File::get_absolute_path( get_site_option( 'backwpup_cfg_logfolder' ) )
		);

		return $log_folder_message;
	}
}
