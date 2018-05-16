<?php
/**
 * Ftp Destination Connect to Service
 *
 * @since   3.5.0
 * @package Inpsyde\BackWPup
 */

/**
 * Class BackWPup_Destination_Ftp_Connect
 *
 * @since   3.5.0
 * @package Inpsyde\BackWPup
 */
class BackWPup_Destination_Ftp_Connect implements BackWPup_Destination_Connect_Interface {

	/**
	 * Host
	 *
	 * @since 3.5.0
	 *
	 * @var string The host to connect
	 */
	private $host;

	/**
	 * User
	 *
	 * @since 3.5.0
	 *
	 * @var string The user name
	 */
	private $user;

	/**
	 * Pass
	 *
	 * @since 3.5.0
	 *
	 * @var string The user password
	 */
	private $pass;

	/**
	 * Port
	 *
	 * @since 3.5.0
	 *
	 * @var int The port where the resource is listen for
	 */
	private $port;

	/**
	 * Connection timeout
	 *
	 * @since 3.5.0
	 *
	 * @see   ftp_connect()
	 *
	 * @var int The connectin timeout
	 */
	private $timeout;

	/**
	 * Use ssl
	 *
	 * @since 3.5.0
	 *
	 * @var bool To use ssl or not
	 */
	private $use_ssl;

	/**
	 * Passive
	 *
	 * @since 3.5.0
	 *
	 * @var bool True to set passive mode, false otherwise
	 */
	private $passive;

	/**
	 * The Resource or Stream to the service
	 *
	 * @since 3.5.0
	 *
	 * @var mixed Depending on the type of the connection
	 */
	private $resource;

	/**
	 * BackWPup_Destination_Ftp_Connect constructor
	 *
	 * @since 3.5.0
	 *
	 * @param string $host    The host to connect.
	 * @param string $user    The user name for host.
	 * @param string $pass    The password for host.
	 * @param int    $port    The port in which the service is listen for.
	 * @param int    $timeout The connection timeout.
	 * @param bool   $use_ssl To connect over ssl.
	 * @param bool   $passive Connetion should be passive or not.
	 */
	public function __construct( $host, $user, $pass, $port, $timeout, $use_ssl, $passive ) {

		if ( ! is_string( $host ) || '' === $host ) {
			throw new \InvalidArgumentException( 'Invalid HOST value. The host must be a valid string.' );
		}
		if ( ! is_string( $user ) || '' === $user ) {
			throw new \InvalidArgumentException( 'Invalid USER value. The user must be a valid username string.' );
		}
		if ( ! is_string( $pass ) || '' === $pass ) {
			throw new \InvalidArgumentException( 'Invalid PASSWORD value. The user must be a valid password string.' );
		}

		$this->host     = $host;
		$this->user     = $user;
		$this->pass     = $pass;
		$this->port     = $port ?: 21;
		$this->timeout  = $timeout ?: 90;
		$this->use_ssl  = function_exists( 'ftp_ssl_connect' ) && $use_ssl;
		$this->passive  = (bool) $passive;
		$this->resource = null;
	}

	/**
	 * @inheritdoc
	 */
	public function connect() {

		// Don't execute the connection twice.
		if ( $this->resource ) {
			return $this;
		}

		if ( ! function_exists( 'ftp_connect' ) ) {
			throw new \BackWPup_Destination_Connect_Exception(
				'Function ftp_connect does not exists. No way to connect to the server.'
			);
		}

		// Default connection type.
		$resource = ftp_connect( $this->host, $this->port, $this->timeout );

		// Can be connected over ssl?
		if ( $this->use_ssl ) {
			$resource = ftp_ssl_connect( $this->host, $this->port, $this->timeout );
		}

		if ( ! $resource ) {
			throw new \BackWPup_Destination_Connect_Exception(
				'Something went wrong during FTP connection. Seems not possible to connect to the service.'
			);
		}

		$this->resource = $resource;

		if ( ! $this->login() ) {
			throw new BackWPup_Destination_Connect_Exception(
				'Something went wrong during FTP connection. Seems not possible to connect to the service.'
			);
		}

		ftp_pasv( $this->resource, $this->passive );

		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function resource() {

		return $this->resource;
	}
	
	/**
		 * Get the FTP URL
		 *
		 * @param string $path The path to the FTP file
		 *
		 * @return string The URL to the FTP server.
		 */
	public function getURL( $path = null ) {

		if ( $path !== null && substr( $path, 0, 1 ) != '/' ) {
			$path = '/' . $path;
		}
		
		return ( $this->use_ssl ? 'ftps://' : 'ftp://' ) .
		rawurlencode( $this->user ) . ':' . rawurlencode( $this->pass ) .
		'@' . rawurlencode( $this->host ) . ':' . intval( $this->port ) . $path;
	}

	/**
	 * Login
	 *
	 * Perform a login to the service.
	 *
	 * @throws BackWPup_Destination_Connect_Exception In case isn't possible to login.
	 *
	 * @since 3.5.0
	 *
	 * @return bool True if was able to login, false otherwise.
	 */
	private function login() {

		$response = false;

		if ( ftp_login( $this->resource, $this->user, $this->pass ) ) {
			$response = true;
		}

		if ( ! $response ) {
			ftp_raw( $this->resource, 'USER ' . $this->user );
			$response = ftp_raw( $this->resource, 'PASS ' . $this->pass );
		}

		// Since the ftp_raw returns an array, we check for false value.
		if ( is_array( $response ) && substr( trim( $response[0] ), 0, 3 ) > 400 ) {
			$response = false;
		}

		return $response;
	}
}
