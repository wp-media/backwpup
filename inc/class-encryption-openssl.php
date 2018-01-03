<?php

/**
 * Encrypt / decrypt data using Open SSL.
 *
 * This is the method used with PHP 5.3+ and open SSL lib available.
 *
 * Normally we should implement an interface, but at the moment with PHP 5.2 and the "poor" autoloader we have,
 * an interface would just be another file in "/inc" folder causing confusion. Also, I don't want to create an
 * interface in a file named `class-...php`. When we get rid of PHP 5.2, we setup a better autoloader and we get rid of
 * WP coding standard, we finally could consider to introduce an interface.
 */
class BackWPup_Encryption_OpenSSL {

	const PREFIX = 'OSSL$';

	private static $cipher_method;

	/**
	 * @return bool
	 */
	public static function supported() {

		return
			version_compare( PHP_VERSION, '5.3.0', '>=' )
			&& function_exists( 'openssl_get_cipher_methods' )
			&& self::cipher_method();
	}

	/**
	 * @return string
	 */
	private static function cipher_method() {

		if ( is_string( self::$cipher_method ) ) {
			return self::$cipher_method;
		}

		$all_methods = openssl_get_cipher_methods();
		if ( ! $all_methods ) {
			self::$cipher_method = '';

			return '';
		}

		$preferred = array( 'AES-256-CTR', 'AES-128-CTR', 'AES-192-CTR' );
		foreach ( $preferred as $method ) {
			if ( in_array( $method, $all_methods, true ) ) {
				self::$cipher_method = $method;

				return $method;
			}
		}

		self::$cipher_method = reset( $all_methods );

		return self::$cipher_method;
	}

	/**
	 * @param string $enc_key
	 * @param string $key_type
	 */
	public function __construct( $enc_key, $key_type ) {

		$this->key      = md5( (string) $enc_key );
		$this->key_type = (string) $key_type;
	}

	/**
	 *
	 * Encrypt a string using Open SSL lib with  AES-256-CTR cypher
	 *
	 * @param string $string value to encrypt.
	 *
	 * @return string encrypted string
	 */
	public function encrypt( $string ) {

		if ( ! is_string( $string ) || ! $string ) {
			return '';
		}

		$base64_encode = '';
		$args          = array(
			$string,
			self::cipher_method(),
			$this->key,
			OPENSSL_RAW_DATA,
		);

		if ( 1 === version_compare( PHP_VERSION, '5.3.2' ) ) {
			$args[] = openssl_random_pseudo_bytes( openssl_cipher_iv_length( self::cipher_method() ) );

			// Include nonce if possible.
			$base64_encode .= end( $args );
		}

		$encrypted = call_user_func_array( 'openssl_encrypt', $args );

		$base64_encode .= $encrypted;

		return BackWPup_Encryption::PREFIX . self::PREFIX . $this->key_type . base64_encode( $base64_encode );
	}

	/**
	 *
	 * Decrypt a string using Open SSL lib with  AES-256-CTR cypher
	 *
	 * @param string $string value to decrypt.
	 *
	 * @return string decrypted string
	 */
	public function decrypt( $string ) {

		if (
			! is_string( $string )
			|| ! $string
			|| strpos( $string, BackWPup_Encryption::PREFIX . self::PREFIX . $this->key_type ) !== 0
		) {
			return '';
		}

		$no_prefix  = substr( $string, strlen( BackWPup_Encryption::PREFIX . self::PREFIX . $this->key_type ) );
		$encrypted  = base64_decode( $no_prefix, true );
		$to_decrypt = $encrypted;
		$args       = array(
			$to_decrypt,
			self::cipher_method(),
			$this->key,
			OPENSSL_RAW_DATA,
		);

		if ( $encrypted === false ) {
			return '';
		}

		if ( 1 === version_compare( PHP_VERSION, '5.3.2' ) ) {
			$nonce_size = openssl_cipher_iv_length( self::cipher_method() );
			$nonce      = substr( $encrypted, 0, $nonce_size );

			$args[0] = substr( $encrypted, $nonce_size );
			$args[]  = $nonce;
		}

		return call_user_func_array( 'openssl_decrypt', $args );
	}
}
