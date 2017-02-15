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
class Inpsyde_PhoneHome_HttpClient {

	/**
	 * @var Inpsyde_PhoneHome_Configuration
	 */
	private $configuration;

	/**
	 * @param Inpsyde_PhoneHome_Configuration $configuration
	 */
	public function __construct( Inpsyde_PhoneHome_Configuration $configuration ) {
		$this->configuration = $configuration;
	}

	/**
	 * @param array $data
	 *
	 * @return bool
	 */
	public function send_data( array $data = array() ) {

		if ( ! $data ) {
			return false;
		}

		$target_url =
			trailingslashit( $this->configuration->server_address() )
			. ltrim( $this->configuration->server_endpoint(), '/' );

		if ( ! filter_var( $target_url, FILTER_VALIDATE_URL ) ) {
			return false;
		}

		$args = array(
			'method'   => $this->configuration->server_method(),
			'timeout'  => 0.01,
			'blocking' => false,
			'body'     => $data,
		);

		$user = $this->configuration->server_basic_auth_user();
		$pass = $this->configuration->server_basic_auth_pass();

		if ( $user && $pass ) {
			$args[ 'headers' ] = array( 'Authorization' => 'Basic ' . base64_encode( "{$user}:{$pass}" ) );
		}

		$response = wp_remote_request( $target_url, $args );

		return ! is_wp_error( $response );
	}
}