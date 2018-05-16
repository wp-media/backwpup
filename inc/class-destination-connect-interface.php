<?php
/**
 * Destination Connect Interface
 *
 * @since   3.5.0
 * @package Inpsyde\BackWPup
 */

/**
 * Class BackWPup_Destination_Connect_Interface
 *
 * @since   3.5.0
 * @package Inpsyde\BackWPup
 */
interface BackWPup_Destination_Connect_Interface {

	/**
	 * Connect
	 *
	 * @since 3.5.0
	 *
	 * @return BackWPup_Destination_Connect_Interface The instance of concatenation
	 */
	public function connect();

	/**
	 * Retreive the Resource
	 *
	 * The resource is generally an instance of the class that manage the connection / stream to the resource.
	 *
	 * @since 3.5.0
	 *
	 * @return mixed Depending on the destination
	 */
	public function resource();
}
