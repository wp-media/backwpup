<?php
/**
 * BackWPup_Destination_Downloader_Factory.
 *
 * @since   3.5.0
 */

/**
 * Class BackWPup_Destination_Downloader_Factory.
 *
 * @since   3.5.0
 */
class BackWPup_Destination_Downloader_Factory {

	public const CLASS_PREFIX     = 'BackWPup_Destination_';
	public const CLASS_PRO_PREFIX = 'BackWPup_Pro_Destination_';
	public const CLASS_SUFFIX     = '_Downloader';

	/**
	 * Create a destination downloader.
	 *
	 * @param string $service_name
	 * @param int    $job_id
	 * @param string $source_file_path
	 * @param string $local_file_path
	 *
	 * @return \BackWPup_Destination_Downloader
	 * @throws BackWPup_Factory_Exception When the destination class does not exist.
	 */
	public function create( $service_name, $job_id, $source_file_path, $local_file_path ) {
		$destination  = null;
		$service_name = ucwords( $service_name );
		$class        = self::CLASS_PREFIX . $service_name . self::CLASS_SUFFIX;

		// If class doesn't exist, try within the Pro directory.
		if ( BackWPup::is_pro() && ! class_exists( $class ) ) {
			$class = str_replace( self::CLASS_PREFIX, self::CLASS_PRO_PREFIX, $class );
		}

		if ( ! class_exists( $class ) ) {
			throw new BackWPup_Factory_Exception(
				sprintf(
					// translators: %s = Destination class name.
					esc_html__( 'No way to instantiate class %s. Class doesn\'t exist.', 'backwpup' ),
					esc_html( $class )
				)
			);
		}

		$data = new BackWpUp_Destination_Downloader_Data( $job_id, $source_file_path, $local_file_path );

		/**
		 * Destination downloader instance.
		 *
		 * @var \BackWPup_Destination_Downloader_Interface $destination
		 */
		$destination = new $class( $data );

		return new BackWPup_Destination_Downloader( $data, $destination );
	}
}
