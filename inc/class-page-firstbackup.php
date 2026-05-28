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

	/**
	 * Render log HTML through the shared log facade when available.
	 *
	 * @param string $content Raw HTML log file fragment.
	 *
	 * @return string
	 */
	public static function render_log_content( string $content ): string {
		$container = wpm_apply_filters_typed( '?object', 'backwpup_container', null );
		if ( is_object( $container ) && method_exists( $container, 'has' ) && $container->has( 'log_facade' ) ) {
			$log_facade = $container->get( 'log_facade' );
			if ( is_object( $log_facade ) && method_exists( $log_facade, 'render_html' ) ) {
				return $log_facade->render_html( $content );
			}
		}

		$log_facade = new \WPMedia\BackWPup\Log\LogFacade();
		return $log_facade->render_html( $content );
	}
}
