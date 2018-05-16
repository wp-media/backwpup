<?php
/**
 * BackWPup_Destination_Factory
 *
 * @since   3.5.0
 * @package Inpsyde\BackWPup
 */

/**
 * Class BackWPup_Destination_Factory
 *
 * @since   3.5.0
 * @package Inpsyde\BackWPup
 */
class BackWPup_Destination_Factory {

	/**
	 * Destination
	 *
	 * @var string The destination identifier
	 */
	private $destination;

	/**
	 * Class Prefix
	 *
	 * @var string The class prefix. The part before the destination
	 */
	private static $prefix = 'BackWPup_Destination_';

	/**
	 * Class Prefix for Pro Classes
	 *
	 * @since 3.5.0
	 *
	 * @var string The class prefix for pro classe
	 */
	private static $pro_prefix = 'BackWPup_Pro_Destination_';

	/**
	 * BackWPup_Destination_Factory constructor
	 *
	 * @param string $destination The destination name.
	 */
	public function __construct( $destination ) {

		$this->destination = $destination;
	}

	/**
	 * Create
	 *
	 * Creates the specified destination object
	 *
	 * @return BackWPup_Destinations
	 */
	public function create() {

		// Build the class name.
		$class = self::$prefix . $this->destination;

		// If class doesn't exists, try within the Pro directory.
		if ( ! class_exists( $class ) ) {
			$class = str_replace( self::$prefix, self::$pro_prefix, $class );
		}

		if ( ! class_exists( $class ) ) {
			throw new BackWPup_Factory_Exception(
				sprintf(
					'No way to instantiate class %s. Class doesn\'t exists.',
					$class
				)
			);
		}

		return new $class();
	}
}
