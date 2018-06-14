<?php
/**
 * BackWPup_Factory_Interface
 *
 * @since   3.5.0
 * @package Inpsyde\BackWPup
 */

/**
 * Class BackWPup_Factory_Interface
 *
 * @since   3.5.0
 * @package Inpsyde\BackWPup
 */
interface BackWPup_Factory_Interface {

	/**
	 * Create Destination Instance
	 *
	 * @since 3.5.0
	 *
	 * @throws BackWPup_Factory_Exception If the class we want to instantiate doesn't exists.
	 *
	 * @return BackWPup_Destination_Downloader
	 */
	public function create();
}
