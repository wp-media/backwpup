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

	/**
	 * Prefix.
	 *
	 * @var string
	 */
	public const PREFIX = 'OSSL$';

	/**
	 * Cipher Method.
	 *
	 * @var string
	 */
	private static $cipher_method;

	/**
	 * Encryption key.
	 *
	 * @var string
	 */
	private $key;

	/**
	 * Encryption key type.
	 *
	 * @var string
	 */
	private $key_type;

	/**
	 * BackWPup_Encryption_OpenSSL constructor.
	 *
	 * @param string $enc_key  Encryption key.
	 * @param string $key_type Key type identifier.
	 */
	public function __construct( $enc_key, $key_type ) {
		$this->key      = md5( (string) $enc_key );
		$this->key_type = (string) $key_type;
	}

	/**
	 * Supported.
	 *
	 * @return bool
	 */
	public static function supported() {
		return function_exists( 'openssl_get_cipher_methods' )
			&& self::cipher_method();
	}

	/**
	 * Encrypt a string using Open SSL lib with  AES-256-CTR cypher.
	 *
	 * @param string $value Value to encrypt.
	 *
	 * @return string Encrypted string.
	 */
	public function encrypt( $value ) {
		if ( ! is_string( $value ) || ! $value ) {
			return '';
		}

		$nonce            = openssl_random_pseudo_bytes( openssl_cipher_iv_length( self::cipher_method() ) );
		$openssl_raw_data = defined( 'OPENSSL_RAW_DATA' ) ? OPENSSL_RAW_DATA : 1;
		$encrypted        = openssl_encrypt(
			$value,
			self::cipher_method(),
			$this->key,
			$openssl_raw_data,
			$nonce
		);

		return BackWPup_Encryption::PREFIX . self::PREFIX . $this->key_type . base64_encode( $nonce . $encrypted ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Binary-safe encoding.
	}

	/**
	 * Decrypt a string using Open SSL lib with  AES-256-CTR cypher.
	 *
	 * @param string $value Value to decrypt.
	 *
	 * @return string Decrypted string.
	 */
	public function decrypt( $value ) {
		if (
			! is_string( $value )
			|| ! $value
			|| 0 !== strpos( $value, BackWPup_Encryption::PREFIX . self::PREFIX . $this->key_type )
		) {
			return '';
		}

		$no_prefix = substr(
			$value,
			strlen( BackWPup_Encryption::PREFIX . self::PREFIX . $this->key_type )
		);

		$encrypted = base64_decode( $no_prefix, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Binary-safe decoding.
		if ( false === $encrypted ) {
			return '';
		}

		$nonce_size       = openssl_cipher_iv_length( self::cipher_method() );
		$nonce            = substr( $encrypted, 0, $nonce_size );
		$to_decrypt       = substr( $encrypted, $nonce_size );
		$openssl_raw_data = defined( 'OPENSSL_RAW_DATA' ) ? OPENSSL_RAW_DATA : true;

		return openssl_decrypt(
			$to_decrypt,
			self::cipher_method(),
			$this->key,
			$openssl_raw_data,
			$nonce
		);
	}

	/**
	 * Cipher Method.
	 *
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

		$preferred = [ 'AES-256-CTR', 'AES-128-CTR', 'AES-192-CTR' ];

		foreach ( $preferred as $method ) {
			if ( in_array( $method, $all_methods, true ) ) {
				self::$cipher_method = $method;

				return $method;
			}
		}

		self::$cipher_method = reset( $all_methods );

		return self::$cipher_method;
	}
}
