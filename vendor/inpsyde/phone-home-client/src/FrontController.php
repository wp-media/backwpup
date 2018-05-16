<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the Inpsyde phone-home-client package.
 *
 * (c) 2017 Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package phone-home-client
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License, version 3
 */
final class Inpsyde_PhoneHome_FrontController {

	/**
	 * @var bool|int
	 */
	private static $should_display = 0;

	/**
	 * @var string[]
	 */
	private static $menu_page_ids = array();

	/**
	 * @var Inpsyde_PhoneHome_Configuration
	 */
	private $configuration;

	/**
	 * @var Inpsyde_PhoneHome_Template_Loader
	 */
	private $template_loader;

	/**
	 * @var string
	 */
	private $page_id = '';

	/**
	 * @var string
	 */
	private $plugin_name;

	/**
	 * @var string
	 */
	private $parent_menu;

	/**
	 * @param string $plugin_name
	 * @param string $templates_dir
	 * @param array  $configs
	 * @param string $parent_menu
	 * @param bool   $network
	 */
	public static function initialize(
		$plugin_name,
		$templates_dir,
		array $configs = array(),
		$parent_menu = '',
		$network = false
	) {

		$instance = new Inpsyde_PhoneHome_FrontController(
			(string) $plugin_name,
			new Inpsyde_PhoneHome_Configuration( $configs ),
			new Inpsyde_PhoneHome_Template_Loader( $templates_dir ),
			$parent_menu
		);

		$instance->setup( $network );
	}

	/**
	 * @param string $plugin_name
	 * @param string $templates_dir
	 * @param string $parent_menu
	 * @param array  $configs
	 */
	public static function initialize_for_network(
		$plugin_name,
		$templates_dir,
		$parent_menu = '',
		array $configs = array()
	) {
		self::initialize( $plugin_name, $templates_dir, $configs, $parent_menu, true );
	}

	/**
	 * @param string                            $plugin_name
	 * @param Inpsyde_PhoneHome_Configuration   $configuration
	 * @param Inpsyde_PhoneHome_Template_Loader $template_loader
	 * @param string                            $parent_menu
	 */
	public function __construct(
		$plugin_name,
		Inpsyde_PhoneHome_Configuration $configuration,
		Inpsyde_PhoneHome_Template_Loader $template_loader,
		$parent_menu = ''
	) {
		if ( ! is_string( $parent_menu ) || ! $parent_menu ) {
			$parent_menu = "index.php";
		}

		$this->plugin_name     = (string) $plugin_name;
		$this->parent_menu     = $parent_menu;
		$this->configuration   = $configuration;
		$this->template_loader = $template_loader;
	}

	/**
	 * @param bool $network
	 */
	public function setup( $network ) {

		$this->load_translations();
		$this->setup_cron_controller();

		if ( is_admin() ) {

			$menu_hook = ( is_multisite() && $network ) ? 'network_admin_menu' : 'admin_menu';
			add_action( $menu_hook, array( $this, 'setup_menu' ), PHP_INT_MAX );

			$this->setup_action_controller();
		}
	}

	/**
	 * Add a menu (or a submenu page) hooking a callback will print the ask for consent page.
	 * The id of the menu page is stored in a class property, so that we can recognize when current page
	 * is the the one associated with a consent page and avoid to print the notice in that page.
	 */
	public function setup_menu() {

		if ( ! $this->should_display_page( $this->plugin_name, true ) ) {
			return;
		}

		$page_title     = esc_html( sprintf( __( '%s needs your help', 'inpsyde-phone-home' ), $this->plugin_name ) );
		$menu_title     = esc_html( sprintf( __( 'Help %s', 'inpsyde-phone-home' ), $this->plugin_name ) );
		$menu_page_slug = sanitize_title( $this->plugin_name ) . '-phone-home-consent';

		$menu_page_id = add_submenu_page(
			$this->parent_menu,
			$page_title,
			$menu_title,
			$this->configuration->minimum_capability(),
			$menu_page_slug,
			array( $this, 'print_consent_page' )
		);

		$this->setup_admin_notice( $menu_page_id, $menu_page_slug );
	}

	/**
	 * Load consent request template and print it.
	 */
	public function print_consent_page() {

		if ( ! $this->should_display_page( $this->plugin_name ) ) {
			return;
		}

		$screen       = did_action( 'current_screen' ) ? get_current_screen() : '';
		$current_slug = '';

		if ( isset( $screen->id ) && isset( self::$menu_page_ids[ $screen->id ] ) ) {
			$current_slug = self::$menu_page_ids[ $screen->id ];
		}

		if ( $current_slug && $current_slug !== $this->page_id ) {
			return;
		}

		echo $this->template_loader->load(
			Inpsyde_PhoneHome_Template_Loader::TEMPLATE_QUESTION,
			array(
				'plugin_name' => $this->plugin_name,
				'anonymize'   => $this->configuration->anonymize()
			)
		);

	}

	/**
	 * Load notice template and print it.
	 */
	public function print_notice() {

		if ( ! $this->should_display_page( $this->plugin_name ) ) {
			return;
		}

		$screen = did_action( 'current_screen' ) ? get_current_screen() : '';

		// When in our menu page no need to also show the notice
		if ( ! isset( $screen->id ) || isset( self::$menu_page_ids[ $screen->id ] ) ) {
			return;
		}

		// Get chance to filter places where to show notice
		if ( ! apply_filters( 'inpsyde-phone-home-show_notice', true, $screen ) ) {
			return;
		}

		$template = Inpsyde_PhoneHome_Template_Loader::TEMPLATE_NOTICE;

		echo $this->template_loader->load(
			$template,
			array(
				'plugin_name'   => $this->plugin_name,
				'more_info_url' => menu_page_url( $this->page_id, false ),
				'anonymize'     => $this->configuration->anonymize()
			)
		);
	}

	/**
	 * If user gave consent setup a cron event to phone data home
	 */
	private function setup_cron_controller() {
		$consent = new Inpsyde_PhoneHome_Consent( $this->plugin_name );
		if ( $consent->agreed() ) {
			$http_client     = new Inpsyde_PhoneHome_HttpClient( $this->configuration );
			$cron_controller = new Inpsyde_PhoneHome_CronController( $http_client, $consent );
			$cron_controller->schedule();
		}
	}

	/**
	 * Adds the AJAX handler callback to all the handled actions.
	 */
	private function setup_action_controller() {

		$controller = new Inpsyde_PhoneHome_ActionController(
			$this->plugin_name,
			$this->parent_menu,
			$this->configuration
		);

		$controller->setup();
	}

	/**
	 * Stores id of given menu page is stored in a class property, so that we can recognize when current page
	 * is the the one associated with a consent page and avoid to print the notice in that page.
	 * Also takes care of hooking 'admin_notices' to display the notice.
	 *
	 * @param string $menu_page_id
	 * @param string $menu_page_slug
	 */
	private function setup_admin_notice( $menu_page_id, $menu_page_slug ) {

		if ( is_string( $menu_page_id ) && $menu_page_id ) {
			self::$menu_page_ids[ $menu_page_id ] = $menu_page_slug;
			$this->page_id                        = $menu_page_slug;
			add_action( 'admin_notices', array( $this, 'print_notice' ) );
		}
	}

	/**
	 * Returns true when all the condition to display the notice of the ask for consent page are there.
	 *
	 * @param string $plugin_name
	 * @param bool   $early_check
	 *
	 * @return bool
	 */
	private function should_display_page( $plugin_name, $early_check = false ) {

		static $random_flag;
		if ( ! isset( $random_flag ) ) {
			$random_flag = rand( 1, 999 );
		}

		if ( is_bool( self::$should_display ) ) {

			// We checked everything no need to check again
			return self::$should_display;

		} elseif ( $early_check && self::$should_display === $random_flag ) {

			return true;

		} elseif ( ! $early_check && self::$should_display === $random_flag ) {

			// We need to check page id, let's do it and then update the static flag for next calls
			self::$should_display = $this->page_id ? true : false;

			return self::$should_display;
		}

		$user = wp_get_current_user();

		if (
			( ! $this->page_id && ! $early_check )
			|| ! $user
			|| ! user_can( $user, $this->configuration->minimum_capability() )
		) {
			self::$should_display = false;

			return self::$should_display;
		}

		// If we already got agreement or user decided to hide the notice we do nothing.
		$display_controller = new Inpsyde_PhoneHome_Consent_DisplayController( $plugin_name );

		if ( ! $user || ! $display_controller->should_show( $user ) ) {
			self::$should_display = false;

			return self::$should_display;
		}

		// When running early, we checked everything but page id, so we store the static flag to a fixed random
		// number so that on next call we can recognize this status
		self::$should_display = $early_check ? $random_flag : true;

		return self::$should_display;
	}

	/**
	 * Load package .mo file
	 */
	private function load_translations() {

		$path    = dirname( dirname( __FILE__ ) ) . '/languages/';
		$locale  = is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
		$mo_file = apply_filters( 'inpsyde-phone-home-mo-file', "{$path}/inpsyde-phone-home-{$locale}.mo", $locale );

		load_textdomain( 'inpsyde-phone-home', $mo_file );
	}

}