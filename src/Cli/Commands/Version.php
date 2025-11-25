<?php

namespace WPMedia\BackWPup\Cli\Commands;

class Version implements Command {

	/**
	 * An array containing plugin data.
	 *
	 * @var array $plugin_data
	 */
	private array $plugin_data;

	/**
	 * Class constructor.
	 *
	 * @param array $plugin_data An array containing plugin data.
	 *
	 * @return void
	 */
	public function __construct( array $plugin_data ) {
		$this->plugin_data = $plugin_data;
	}

	/**
	 * Show BackWPup Plugin Version.
	 *
	 * ## OPTIONS
	 *
	 * [--debug]
	 * : Show debug information (Global option).
	 *
	 * ## EXAMPLES
	 *
	 *     # Show BackWPup Plugin Version.
	 *     $ wp backwpup version
	 *     BackWPup 5.6.0
	 *
	 *     # Show BackWPup Plugin Version and BackWPup Settings.
	 *     $ wp backwpup version --debug
	 *     BackWPup 5.6.0
	 *     Debug: Document root: /var/www/html/(0.487s)
	 *     Debug: Temp folder: /var/www/html/wp-content/uploads/backwpup/d14761/temp/ (0.487s)
	 *     Debug: Log folder: /var/www/html/wp-content/uploads/backwpup/d14761/logs/ (0.487s)
	 *     Debug: Server:  (0.487s)
	 *     Debug: Operating System: Linux (0.487s)
	 *     Debug: PHP SAPI: cli (0.487s)
	 *     Debug: Current PHP user: root (0.487s)
	 *     Debug: Maximum execution time: 0 seconds (0.487s)
	 *     Debug: BackWPup maximum script execution time: 30 seconds (0.487s)
	 *     Debug: Alternative WP Cron: Off (0.487s)
	 *     Debug: Disabled WP Cron: Off (0.487s)
	 *     Debug: WP Cron is working: Yes (0.487s)
	 *     Debug: CHMOD Dir: 0755 (0.487s)
	 *     Debug: Server Time: 10:27 (0.487s)
	 *     Debug: Blog Time: 10:27 (0.487s)
	 *     Debug: Blog Timezone:  (0.487s)
	 *     Debug: Blog Time offset: 0 hours (0.487s)
	 *     Debug: Blog language: en-US (0.487s)
	 *     Debug: MySQL Client encoding: utf8 (0.487s)
	 *     Debug: PHP Memory limit: -1 (0.487s)
	 *     Debug: WP memory limit: 40M (0.487s)
	 *     Debug: WP maximum memory limit: -1 (0.487s)
	 *     Debug: Memory in use: 12.00 MB (0.487s)
	 *     Debug: Loaded PHP Extensions: Core, ... (0.487s)
	 *
	 * @param array $args Positional arguments passed to the method.
	 * @param array $assoc_args Associative arguments passed to the method.
	 *
	 * @return void
	 */
	public function __invoke( array $args, array $assoc_args ) {

		\WP_CLI::line( $this->plugin_data['name'] . ' ' . $this->plugin_data['version'] );

		$information = \BackWPup_Page_Settings::get_information();
		foreach ( $information as $item ) {
			\WP_CLI::debug( esc_html( $item['label'] ) . ': ' . esc_html( $item['value'] ) );
		}
	}


	/**
	 * Retrieves the command name.
	 *
	 * @inheritDoc
	 */
	public function get_name(): string {
		return 'version';
	}

	/**
	 * Retrieves the arguments for the command.
	 *
	 * @inheritDoc
	 */
	public function get_args(): array {
		return [];
	}
}
