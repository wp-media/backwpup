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
class Inpsyde_PhoneHome_ActionController {

	const ACTION_AGREE = 'inpsyde_phone-home_agree';
	const ACTION_DISAGREE = 'inpsyde_phone-home_disagree';
	const ACTION_MAYBE = 'inpsyde_phone-home_maybe';

	const NONCE_KEY = 'nonce';
	const USER_KEY = 'user';
	const ACTION_KEY = 'action';

	/**
	 * @var string[]
	 */
	private static $action_map = array(
		self::ACTION_AGREE    => 'agree',
		self::ACTION_DISAGREE => 'disagree',
		self::ACTION_MAYBE    => 'maybe',
	);

	/**
	 * @var Inpsyde_PhoneHome_Configuration
	 */
	private $configuration;

	/**
	 * @var string
	 */
	private $plugin_name;

	/**
	 * @var string
	 */
	private $parent_menu;

	/**
	 * @param string $action
	 *
	 * @return string
	 */
	public static function url_for_action( $action ) {

		if ( ! array_key_exists( $action, self::$action_map ) ) {
			return '';
		}

		$data = array(
			self::NONCE_KEY  => wp_create_nonce( $action ),
			self::USER_KEY   => get_current_user_id(),
			self::ACTION_KEY => $action,
		);

		return add_query_arg( $data, admin_url( 'admin-post.php' ) );
	}

	/**
	 * @param string                          $plugin_name
	 * @param string                          $parent_menu
	 * @param Inpsyde_PhoneHome_Configuration $configuration
	 */
	public function __construct( $plugin_name, $parent_menu, Inpsyde_PhoneHome_Configuration $configuration ) {

		$this->plugin_name   = (string) $plugin_name;
		$this->parent_menu   = (string) $parent_menu;
		$this->configuration = $configuration;
	}

	/**
	 * Adds the action to dispatch method that will then proxy to inner method based on received data.
	 */
	public function setup() {

		$actions = array(
			self::ACTION_AGREE,
			self::ACTION_DISAGREE,
			self::ACTION_MAYBE,
		);

		foreach ( $actions as $action ) {
			add_action( "admin_post_{$action}", array( $this, 'dispatch' ) );
		}
	}

	/**
	 * Validate data coming from AJAX and call the proper method according to the required action.
	 *
	 * @see Inpsyde_PhoneHome_ActionController::agree()
	 * @see Inpsyde_PhoneHome_ActionController::disagree()
	 * @see Inpsyde_PhoneHome_ActionController::maybe()
	 */
	public function dispatch() {

		$data = $this->data();

		if ( ! $this->should_handle_request( $data ) ) {
			return;
		}

		if ( $this->is_user_allowed( $data ) ) {
			call_user_func( array( $this, self::$action_map[ $data[ self::ACTION_KEY ] ] ) );
		}

		$this->terminate();
	}

	/**
	 * User agreed on phone home. Let's do it.
	 */
	private function agree() {

		$consent     = new Inpsyde_PhoneHome_Consent( $this->plugin_name );
		$http_client = new Inpsyde_PhoneHome_HttpClient( $this->configuration );

		if ( $consent->set_as_agree( $this->configuration ) ) {
			$http_client->send_data( $consent->consent_data() );
		}

	}

	/**
	 * User did not agree on phone home. Let's hide the request for them.
	 */
	private function disagree() {

		$question = new Inpsyde_PhoneHome_Consent_DisplayController( $this->plugin_name );
		$question->hide_for_good( wp_get_current_user() );
	}

	/**
	 *  User did not agree nor agree. Let's hide the request for them for sometime.
	 */
	private function maybe() {

		$question = new Inpsyde_PhoneHome_Consent_DisplayController( $this->plugin_name );
		$question->hide_for_now( wp_get_current_user() );
	}

	/**
	 * @param array $data
	 *
	 * @return bool
	 */
	private function should_handle_request( array $data ) {

		return
			is_admin()
			&& isset( $data[ self::ACTION_KEY ] )
			&& array_key_exists( $data[ self::ACTION_KEY ], self::$action_map );
	}

	/**
	 * @param array $data
	 *
	 * @return bool
	 */
	private function is_user_allowed( array $data ) {

		return
			wp_verify_nonce( $data[ self::NONCE_KEY ], $data[ self::ACTION_KEY ] )
			&& (int) $data[ self::USER_KEY ] === (int) get_current_user_id()
			&& user_can( $data[ self::USER_KEY ], $this->configuration->minimum_capability() );
	}

	/**
	 * @return array
	 */
	private function data() {

		return filter_input_array(
			INPUT_GET,
			array(
				self::NONCE_KEY  => FILTER_SANITIZE_STRING,
				self::USER_KEY   => FILTER_SANITIZE_NUMBER_INT,
				self::ACTION_KEY => FILTER_SANITIZE_STRING,
			)
		);
	}

	/**
	 * @return void
	 */
	private function terminate() {

		$redirect = menu_page_url( $this->parent_menu );
		if ( ! $redirect ) {
			$redirect = ( is_multisite() && is_super_admin() ) ? network_admin_url() : admin_url();
		}

		wp_safe_redirect( $redirect );
		exit();
	}

}