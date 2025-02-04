<?php

class BackWPup_Page_First_Backup {

	/**
	 * Initialize the class and set its properties.
	 */
	public function __construct() {
	}

	/**
	 * Display the "First Backup" page content.
	 */
	public static function page() {
		include untrailingslashit( BackWPup::get_plugin_data( 'plugindir' ) ) . '/pages/first-progress.php';
	}

	/**
	 * Initializes an instance of the class.
	 *
	 * This method creates a new instance of the class and assigns it to the $instance variable.
	 *
	 * @return void
	 */
	public static function init() {
		$instance = new self();
	}
}
