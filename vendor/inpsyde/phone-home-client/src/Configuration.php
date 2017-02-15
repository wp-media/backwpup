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
class Inpsyde_PhoneHome_Configuration {

	const ANONYMIZE = 'anonymize';
	const BY_NETWORK = 'by_network';
	const MINIMUM_CAPABILITY = 'minimum_role';
	const COLLECT_PHP = 'collect_php';
	const COLLECT_WP = 'collect_wp';
	const SERVER_ADDRESS = 'server_address';
	const SERVER_ENDPOINT = 'server_endpoint';
	const SERVER_METHOD = 'server_method';
	const SERVER_BASIC_AUTH = 'server_basic_auth';
	const AUTH_USER = 'username';
	const AUTH_PASS = 'password';

	/**
	 * @var array
	 */
	private static $defaults = array(
		self::ANONYMIZE          => true,
		self::MINIMUM_CAPABILITY => 'customize',
		self::COLLECT_PHP        => true,
		self::COLLECT_WP         => false,
		self::SERVER_ADDRESS     => '',
		self::SERVER_ENDPOINT    => 'inpsyde-phone-home/v1/checkin',
		self::SERVER_METHOD      => 'POST',
		self::SERVER_METHOD      => 'POST',
		self::SERVER_BASIC_AUTH  => array(),
	);

	/**
	 * @var array
	 */
	private $arguments = array();

	/**
	 * @param array $arguments
	 */
	public function __construct( array $arguments = array() ) {
		$this->arguments = filter_var_array(
			array_merge( self::$defaults, $arguments ),
			array(
				self::ANONYMIZE          => FILTER_VALIDATE_BOOLEAN,
				self::MINIMUM_CAPABILITY => FILTER_SANITIZE_STRING,
				self::COLLECT_PHP        => FILTER_VALIDATE_BOOLEAN,
				self::COLLECT_WP         => FILTER_VALIDATE_BOOLEAN,
				self::SERVER_ADDRESS     => FILTER_SANITIZE_URL,
				self::SERVER_ENDPOINT    => FILTER_SANITIZE_URL,
				self::SERVER_METHOD      => FILTER_SANITIZE_STRING,
				self::SERVER_BASIC_AUTH  => array( 'filter' => FILTER_UNSAFE_RAW, 'flags' => FILTER_FORCE_ARRAY ),
			)
		);
	}

	/**
	 * Returns true if we have a valid server full URL and we have something to collect.
	 *
	 * @return bool
	 */
	public function is_valid() {

		$address  = $this->server_address();
		$endpoint = $this->server_endpoint();

		return
			(bool) filter_var( trailingslashit( $address ) . ltrim( $endpoint, '/' ), FILTER_VALIDATE_URL )
			&& ( $this->collect_wp() || $this->collect_php() );
	}

	/**
	 * @return bool
	 */
	public function anonymize() {
		return (bool) $this->arguments[ self::ANONYMIZE ];
	}

	/**
	 * @return bool
	 */
	public function minimum_capability() {
		return (string) $this->arguments[ self::MINIMUM_CAPABILITY ];
	}

	/**
	 * @return bool
	 */
	public function collect_php() {
		return (bool) $this->arguments[ self::COLLECT_PHP ];
	}

	/**
	 * @return bool
	 */
	public function collect_wp() {
		return (bool) $this->arguments[ self::COLLECT_WP ];
	}

	/**
	 * @return bool
	 */
	public function server_address() {

		$address  = $this->arguments[ self::SERVER_ADDRESS ];
		$filtered = apply_filters( 'inpsyde-phone-home-server-address', $address );
		if ( $filtered !== $address ) {
			$filtered = filter_var( $filtered, FILTER_SANITIZE_URL );
			$filtered and $address = $filtered;
		}

		return (string) $address;
	}

	/**
	 * @return bool
	 */
	public function server_endpoint() {

		$endpoint = (string) $this->arguments[ self::SERVER_ENDPOINT ];
		$filtered = apply_filters( 'inpsyde-phone-home-server-endpoint', $endpoint );
		if ( $filtered !== $endpoint ) {
			$filtered = filter_var( $filtered, FILTER_SANITIZE_URL );
			$filtered and $endpoint = $filtered;
		}

		return (string) $endpoint;
	}

	/**
	 * @return bool
	 */
	public function server_method() {

		$method = strtoupper( (string) $this->arguments[ self::SERVER_METHOD ] );
		if ( ! in_array( $method, array( 'GET', 'POST' ), true ) ) {
			$method                                 = 'POST';
			$this->arguments[ self::SERVER_METHOD ] = 'POST';
		}

		return $method;
	}

	/**
	 * @return array
	 */
	public function server_basic_auth() {
		return array_change_key_case( (array) $this->arguments[ self::SERVER_BASIC_AUTH ] );
	}

	/**
	 * @return string
	 */
	public function server_basic_auth_user() {
		$auth = $this->server_basic_auth();
		if ( isset( $auth[ self::AUTH_USER ] ) ) {
			return (string) $auth[ self::AUTH_USER ];
		}

		return '';
	}

	/**
	 * @return string
	 */
	public function server_basic_auth_pass() {
		$auth = $this->server_basic_auth();
		if ( isset( $auth[ self::AUTH_PASS ] ) ) {
			return (string) $auth[ self::AUTH_PASS ];
		}

		return '';
	}

}