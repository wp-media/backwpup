<?php
/**
 * Encrypt / decrypt data using mcrypt.
 *
 * This is the method used with PHP 5.2 or when Open SSL is not available.
 *
 * mcrypt is abandonware and has unpatched bugs, however because of PHP 5.2 support we still support it.
 * We also use mcrypt in more modern PHP version when Open SSL is not available and in the case that we are in
 * PHP 7.1 we call mcrypt with the mute operator to avoid warnings.
 *
 * Normally we should implement an interface, but at the moment with PHP 5.2 and the "poor" autoloader we have,
 * an interface would just be another file in "/inc" folder causing confusion. Also, I don't want to create an
 * interface in a file named `class-...php`. When we get rid of PHP 5.2, we setup a better autoloader and we get rid of
 * WP coding standard, we finally could consider to introduce an interface.
 */
class BackWPup_Encryption_Mcrypt {

	const PREFIX = 'RIJNDAEL$';

	/**
	 * @var bool
	 */
	private $deprecated = false;

	/**
	 * @return bool
	 */
	public static function supported() {
		return function_exists( 'mcrypt_encrypt' );
	}

	/**
	 * @param string $enc_key
	 * @param string $key_type
	 */
	public function __construct( $enc_key, $key_type ) {
		$this->key        = md5( (string) $enc_key );
		$this->key_type   = (string) $key_type;
		$this->deprecated = (bool) version_compare( PHP_VERSION, '7.1', '>=' );

		/** @TODO: Should we do something here to inform user about deprecation? */
	}

	/**
	 *
	 * Encrypt a string (Passwords)
	 *
	 * @param string $string value to encrypt
	 *
	 * @return string encrypted string
	 */
	public function encrypt( $string ) {

		if ( ! is_string( $string ) || ! $string ) {
			return '';
		}

		$encrypted = $this->deprecated
			? @mcrypt_encrypt( MCRYPT_RIJNDAEL_256, $this->key, $string, MCRYPT_MODE_CBC, md5( $this->key ) )
			: mcrypt_encrypt( MCRYPT_RIJNDAEL_256, $this->key, $string, MCRYPT_MODE_CBC, md5( $this->key ) );

		return BackWPup_Encryption::PREFIX . self::PREFIX . $this->key_type . base64_encode( $encrypted );

	}

	/**
	 *
	 * Decrypt a string (Passwords)
	 *
	 * @param string $string value to decrypt
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

		$no_prefix = substr( $string, strlen( BackWPup_Encryption::PREFIX . self::PREFIX . $this->key_type ) );

		$encrypted = base64_decode( $no_prefix, true );
		if ( $encrypted === false ) {
			return '';
		}

		if ( defined( 'BACKWPUP_MCRYPT_KEY_MODE' ) && BACKWPUP_MCRYPT_KEY_MODE === 1 ) {
			return $this->decrypt_deprecated( $encrypted );
		}

		$decrypted = $this->deprecated
			? @mcrypt_decrypt( MCRYPT_RIJNDAEL_256, $this->key, $encrypted, MCRYPT_MODE_CBC, md5( $this->key ) )
			: mcrypt_decrypt( MCRYPT_RIJNDAEL_256, $this->key, $encrypted, MCRYPT_MODE_CBC, md5( $this->key ) );

		$skip_deprecated = defined( 'BACKWPUP_MCRYPT_KEY_MODE' ) && BACKWPUP_MCRYPT_KEY_MODE === 2;

		if ( ! $skip_deprecated && ! @wp_check_invalid_utf8( $decrypted ) ) {
			$decrypted = $this->decrypt_deprecated( $encrypted );
		}

		return $decrypted;
	}

	/**
	 * @param string $encrypted
	 *
	 * @return string
	 */
	private function decrypt_deprecated( $encrypted ) {

		$key = md5( $this->key );

		return $this->deprecated
			? @mcrypt_decrypt( MCRYPT_RIJNDAEL_256, $key, $encrypted, MCRYPT_MODE_CBC, md5( $key ) )
			: mcrypt_decrypt( MCRYPT_RIJNDAEL_256, $key, $encrypted, MCRYPT_MODE_CBC, md5( $key ) );
	}
}
