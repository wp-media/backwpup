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
class Inpsyde_PhoneHome_Consent {

	const OPTION_PREFIX = 'inpsyde-phone-consent-given-';

	const PLUGIN_KEY = 'plugin';
	const IDENTIFIER_KEY = 'identifier';
	const PHP_VER_KEY = 'php_version';
	const WP_VER_KEY = 'wp_version';

	/**
	 * @var array
	 */
	private static $allowed_data = array(
		self::PLUGIN_KEY     => '',
		self::IDENTIFIER_KEY => '',
		self::PHP_VER_KEY    => '',
		self::WP_VER_KEY     => '',
	);

	/**
	 * @var string
	 */
	private $plugin_name;

	/**
	 * @param string $plugin_name
	 */
	public function __construct( $plugin_name ) {
		$this->plugin_name = (string) $plugin_name;
	}

	/**
	 * @return string
	 */
	public function plugin() {
		return $this->plugin_name;
	}

	/**
	 * @return bool
	 */
	public function agreed() {

		$data = $this->consent_data();

		return ! empty( $data[ self::IDENTIFIER_KEY ] );
	}

	/**
	 * @return array
	 */
	public function consent_data() {

		$option_name  = self::OPTION_PREFIX . $this->plugin_name;
		$option_value = get_site_option( $option_name, array() );
		if ( $option_value && is_array( $option_value ) && ! empty( $option_value[ self::IDENTIFIER_KEY ] ) ) {
			return array_intersect_key( $option_value, self::$allowed_data );
		}

		return array();
	}

	/**
	 * @param Inpsyde_PhoneHome_Configuration $configuration
	 *
	 * @return bool
	 */
	public function set_as_agree( Inpsyde_PhoneHome_Configuration $configuration ) {

		$current_option_value = $this->consent_data();

		$option_name = self::OPTION_PREFIX . $this->plugin_name;

		$option_value = array(
			self::PLUGIN_KEY     => $this->plugin_name,
			self::IDENTIFIER_KEY => $this->identifier( $configuration ),
		);

		if ( $configuration->collect_php() ) {
			$option_value[ self::PHP_VER_KEY ] = phpversion();
		}

		if ( $configuration->collect_wp() ) {
			global $wp_version;
			$option_value[ self::WP_VER_KEY ] = (string) $wp_version;
		}

		if ( $current_option_value == $option_value ) {
			return true;
		}

		return (bool) update_site_option( $option_name, $option_value );
	}

	/**
	 * @param Inpsyde_PhoneHome_Configuration $configuration
	 *
	 * @return string
	 */
	private function identifier( Inpsyde_PhoneHome_Configuration $configuration ) {

		$main_url       = is_multisite() ? network_site_url() : home_url();
		$url_data       = array_merge( array( 'host' => '', 'path' => '' ), parse_url( $main_url ) );
		$url_normalized = trailingslashit( $url_data[ 'host' ] ) . trim( $url_data[ 'path' ], '/' );
		if ( $configuration->anonymize() ) {
			return md5( md5( $url_normalized ) );
		}

		return $url_normalized;
	}

}