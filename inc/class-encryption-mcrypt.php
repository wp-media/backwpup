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

	public const PREFIX = 'RIJNDAEL$';

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
	 * Whether to use deprecated mcrypt handling.
	 *
	 * @var bool
	 */
	private $deprecated = false;

	/**
	 * Initialize the mcrypt encryptor.
	 *
	 * @param string $enc_key  Encryption key.
	 * @param string $key_type Key type identifier.
	 */
	public function __construct( $enc_key, $key_type ) {
		$this->key        = md5( (string) $enc_key );
		$this->key_type   = (string) $key_type;
		$this->deprecated = (bool) version_compare( PHP_VERSION, '7.1', '>=' );

		// TODO: Decide how to inform users about deprecation.
	}

	/**
	 * Check whether mcrypt is available.
	 *
	 * @return bool
	 */
	public static function supported() {
		return function_exists( 'mcrypt_encrypt' );
	}

	/**
	 * Encrypt a string (Passwords).
	 *
	 * @param string $value Value to encrypt.
	 *
	 * @return string Encrypted string.
	 */
	public function encrypt( $value ) {
		if ( ! is_string( $value ) || ! $value ) {
			return '';
		}

		$encrypted = $this->deprecated
			? @mcrypt_encrypt( MCRYPT_RIJNDAEL_256, $this->key, $value, MCRYPT_MODE_CBC, md5( $this->key ) ) // @phpstan-ignore-line
			: mcrypt_encrypt( MCRYPT_RIJNDAEL_256, $this->key, $value, MCRYPT_MODE_CBC, md5( $this->key ) ); // @phpstan-ignore-line

		return BackWPup_Encryption::PREFIX . self::PREFIX . $this->key_type . base64_encode( (string) $encrypted ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Binary-safe encoding.
	}

	/**
	 * Decrypt a string (Passwords).
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

		$no_prefix = substr( $value, strlen( BackWPup_Encryption::PREFIX . self::PREFIX . $this->key_type ) );

		$encrypted = base64_decode( $no_prefix, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Binary-safe decoding.
		if ( false === $encrypted ) {
			return '';
		}

		if ( defined( 'BACKWPUP_MCRYPT_KEY_MODE' ) && 1 === BACKWPUP_MCRYPT_KEY_MODE ) {
			return $this->decrypt_deprecated( $encrypted );
		}

		$decrypted = $this->deprecated
			? @mcrypt_decrypt( MCRYPT_RIJNDAEL_256, $this->key, $encrypted, MCRYPT_MODE_CBC, md5( $this->key ) ) // @phpstan-ignore-line
			: mcrypt_decrypt( MCRYPT_RIJNDAEL_256, $this->key, $encrypted, MCRYPT_MODE_CBC, md5( $this->key ) ); // @phpstan-ignore-line

		$skip_deprecated = defined( 'BACKWPUP_MCRYPT_KEY_MODE' ) && 2 === BACKWPUP_MCRYPT_KEY_MODE;

		if ( ! $skip_deprecated && ! @wp_check_invalid_utf8( $decrypted ) ) {
			$decrypted = $this->decrypt_deprecated( $encrypted );
		}

		return $decrypted;
	}

	/**
	 * Decrypt using legacy key derivation.
	 *
	 * @param string $encrypted Encrypted value.
	 *
	 * @return string
	 */
	private function decrypt_deprecated( $encrypted ) {
		$key = md5( $this->key );

		return $this->deprecated
			? @mcrypt_decrypt( MCRYPT_RIJNDAEL_256, $key, $encrypted, MCRYPT_MODE_CBC, md5( $key ) ) // @phpstan-ignore-line
			: mcrypt_decrypt( MCRYPT_RIJNDAEL_256, $key, $encrypted, MCRYPT_MODE_CBC, md5( $key ) ); // @phpstan-ignore-line
	}
}
