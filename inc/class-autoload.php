<?php

/**
 * Class BackWPup_Autoloader
 */
class BackWPup_Autoload {

	/**
	 * Base Directory for classes
	 *
	 * @var string The base directory where looking for classes.
	 */
	private $base_dir;

	/**
	 * Base Directory for Pro classes
	 *
	 * @var string The base directory for pro classes.
	 */
	private $base_pro_dir;

	/**
	 * BackWPup_Autoload constructor
	 */
	public function __construct() {

		$this->base_dir     = dirname( __DIR__ ) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR;
		$this->base_pro_dir = $this->base_dir . DIRECTORY_SEPARATOR . 'Pro' . DIRECTORY_SEPARATOR;
	}

	/**
	 * Plugin Autoloader
	 *
	 * Include not existing classes automatically
	 *
	 * @param string $class Class to load from file.
	 *
	 * @return void
	 */
	public function autoloader( $class ) {

		// Not a backwpup class.
		if ( false === strstr( strtolower( $class ), 'backwpup_' ) ) {
			return;
		}

		$dir             = $this->base_dir;
		$class_file_name = $this->convert_classname_to_filename( $class );

		// Pro class request.
		if ( false !== strstr( strtolower( $class ), 'backwpup_pro' ) ) {
			$dir             = $this->base_pro_dir;
			$class_file_name = str_replace( 'pro-', '', $class_file_name );
		}

		if ( ! file_exists( $dir . $class_file_name ) ) {
			// Class file found.
			return;
		}

		require_once $dir . $class_file_name;
	}

	/**
	 * Convert Class name to file name
	 *
	 * @param string $class The class name.
	 *
	 * @return string the filename created from the class
	 */
	private function convert_classname_to_filename( $class ) {

		return 'class-' . str_replace( array( 'backwpup_', '_' ), array(
				'',
				'-',
			), strtolower( $class ) ) . '.php';
	}
}
