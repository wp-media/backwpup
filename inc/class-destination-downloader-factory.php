<?php
/**
 * BackWPup_Destination_Downloader_Factory
 *
 * @since   3.5.0
 * @package Inpsyde\BackWPup
 */

/**
 * Class BackWPup_Destination_Downloader_Factory
 *
 * @since   3.5.0
 * @package Inpsyde\BackWPup
 */
class BackWPup_Destination_Downloader_Factory {

	const CLASS_PREFIX = 'BackWPup_Destination_';
	const CLASS_PRO_PREFIX = 'BackWPup_Pro_Destination_';
	const CLASS_SUFFIX = '_Downloader';

	/**
	 * @param string $service_name
	 * @param int    $job_id
	 * @param string $source_file_path
	 * @param string $local_file_path
	 * @param string $base_url
	 *
	 * @return \BackWPup_Destination_Downloader
	 */
	public function create( $service_name, $job_id, $source_file_path, $local_file_path, $base_url = '' ) {

		$destination = null;
		$service_name = ucwords( $service_name );
		$class = self::CLASS_PREFIX . $service_name . self::CLASS_SUFFIX;

		// If class doesn't exist, try within the Pro directory.
		if ( BackWPup::is_pro() && ! class_exists( $class ) ) {
			$class = str_replace( self::CLASS_PREFIX, self::CLASS_PRO_PREFIX, $class );
		}

		if ( ! class_exists( $class ) ) {
			throw new BackWPup_Factory_Exception(
				sprintf(
					'No way to instantiate class %s. Class doesn\'t exist.',
					$class
				)
			);
		}

		$data = new BackWpUp_Destination_Downloader_Data( $job_id, $source_file_path, $local_file_path );

		/** @var \BackWPup_Destination_Downloader_Interface $destination */
		$destination = new $class( $data );

		return new BackWPup_Destination_Downloader( $data, $destination );
	}
}
