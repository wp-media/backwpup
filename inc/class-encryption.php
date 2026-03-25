<?php
/**
 * Encrypt / decrypt data using the best algorithm available.
 *
 * This should be converted to a real object (instead of a all-static, "poor man namespace" class) and a algorithm
 * factory should be injected, to be used to create an instance of an algorithm instance.
 * However, at the moment with PHP 5.2 and the "poor" autoloader we have, an interface would just be another file in
 * "/inc" folder causing confusion. Also, I don't want to create an interface in a file named `class-...php`.
 * When we get rid of PHP 5.2, we setup a better autoloader and we get rid of WP coding standard, we finally should
 * refactor to proper code.
 */
class BackWPup_Encryption {

	public const PREFIX          = '$BackWPup$';
	public const KEY_TYPE_CUSTOM = '$0';

	/**
	 * Cipher classes keyed by prefix.
	 *
	 * @var array
	 */
	private static $classes = [
		BackWPup_Encryption_OpenSSL::PREFIX  => \BackWPup_Encryption_OpenSSL::class,
		BackWPup_Encryption_Mcrypt::PREFIX   => \BackWPup_Encryption_Mcrypt::class,
		BackWPup_Encryption_Fallback::PREFIX => \BackWPup_Encryption_Fallback::class,
	];

	/**
	 * Encrypt a string using the best algorithm available.
	 *
	 * In case the given string is encrypted with a weaker algorithm, it will first be decrypted then the plain text
	 * obtained is encrypted with the better algorithm available and returned.
	 *
	 * @param string $value Value to encrypt.
	 *
	 * @return string Encrypted string.
	 */
	public static function encrypt( $value ) {
		if ( ! is_string( $value ) || ! $value ) {
			return '';
		}

		try {
			$cypher_class = self::cypher_class_for_string( $value );
		} catch ( Exception $e ) {
			// TODO: Decide how to handle unsupported ciphers for already-encrypted values.

			return $value;
		}

		try {
			[$key, $key_type] = self::get_encrypt_info( $cypher_class, $value );
		} catch ( Exception $e ) {
			// TODO: Decide how to handle missing custom keys for already-encrypted values.

			return $value;
		}

		$best_cipher_class = self::best_cypher();

		// The given string is not encrypted, let's encrypt it and return.
		if ( ! $cypher_class ) {
			/**
			 * Best available cipher instance.
			 *
			 * @var BackWPup_Encryption_OpenSSL|BackWPup_Encryption_Mcrypt|BackWPup_Encryption_Fallback $best_cypher
			 */
			$best_cypher = new $best_cipher_class( $key, $key_type );

			return $best_cypher->encrypt( $value );
		}

		$encryption_count = substr_count( $value, self::PREFIX );

		// The given string is encrypted once using best cypher, let's just return it.
		if ( 1 === $encryption_count && $cypher_class === $best_cipher_class ) {
			return $value;
		}

		/**
		 * Cipher instance for decrypting the existing value.
		 *
		 * @var BackWPup_Encryption_OpenSSL|BackWPup_Encryption_Mcrypt|BackWPup_Encryption_Fallback $cypher
		 */
		$cypher = new $cypher_class( $key, $key_type );

		$value = $cypher->decrypt( $value );

		return self::encrypt( $value );
	}

	/**
	 * Decrypt a string (Passwords).
	 *
	 * @param string $value Value to decrypt.
	 *
	 * @return string Decrypted string.
	 */
	public static function decrypt( $value ) {
		if ( ! is_string( $value ) || ! $value ) {
			return '';
		}

		try {
			$cypher_class = self::cypher_class_for_string( $value );
		} catch ( Exception $e ) {
			// TODO: Decide how to handle unsupported ciphers when decrypting.

			return '';
		}

		if ( ! $cypher_class ) {
			// TODO: Decide how to handle values that appear unencrypted or corrupted.

			return $value;
		}

		try {
			[$key, $key_type] = self::get_encrypt_info( $cypher_class, $value );
		} catch ( Exception $e ) {
			// TODO: Decide how to handle missing custom keys for decryption.
			return '';
		}

		/**
		 * Cipher instance used for decrypting the value.
		 *
		 * @var BackWPup_Encryption_OpenSSL|BackWPup_Encryption_Mcrypt|BackWPup_Encryption_Fallback $cypher
		 */
		$cypher = new $cypher_class( $key, $key_type );

		return trim( stripslashes( $cypher->decrypt( $value ) ), "\0" );
	}

	/**
	 * Get the cypher class used to encrypt the given string.
	 *
	 * @param string $value Encrypted value.
	 *
	 * @return string
	 *
	 * @throws \RuntimeException If the cypher used to encrypt the given string is not supported in current system.
	 */
	private static function cypher_class_for_string( $value ) {
		foreach ( self::$classes as $prefix => $class ) {
			$enc_prefix = self::PREFIX . $prefix;

			if ( 0 !== strpos( $value, $enc_prefix ) ) {
				continue;
			}

			if ( ! call_user_func( [ $class, 'supported' ] ) ) {
				throw new \RuntimeException(
					// Translators: %s is the name of the encryption algorithm that is not supported in the current system.
					sprintf( esc_html__( 'Give string was encrypted using %s but it is not currently supported in this system.', 'backwpup' ), esc_html( $class ) )
				);
			}

			return $class;
		}

		return '';
	}

	/**
	 * Get the best available cypher class.
	 *
	 * @return string
	 */
	private static function best_cypher() {
		foreach ( self::$classes as $prefix => $class ) {
			if ( ! call_user_func( [ $class, 'supported' ] ) ) {
				continue;
			}

			return $class;
		}

		// This should never happen because BackWPup_Encryption_Fallback::supported() always returns true.

		return '';
	}

	/**
	 * Get the encryption key and key type for a value.
	 *
	 * @param string|null $class_name Cipher class name when known.
	 * @param string      $value      Encrypted or plain value.
	 *
	 * @return array
	 * @throws \RuntimeException If the given string was encrypted using a custom key but 'BACKWPUP_ENC_KEY' constant is not defined anymore.
	 */
	private static function get_encrypt_info( $class_name = null, $value = '' ) {
		$default_key = DB_NAME . DB_USER . DB_PASSWORD;

		if ( ! is_string( $class_name ) || ! $class_name ) {
			return defined( 'BACKWPUP_ENC_KEY' )
				? [ BACKWPUP_ENC_KEY, self::KEY_TYPE_CUSTOM ]
				: [ $default_key, '' ];
		}

		$enc_prefix     = self::PREFIX . constant( "{$class_name}::PREFIX" );
		$has_custom_key = 0 === strpos( $value, $enc_prefix . self::KEY_TYPE_CUSTOM );

		if ( $has_custom_key ) {
			if ( ! defined( 'BACKWPUP_ENC_KEY' ) ) {
				throw new \RuntimeException(
					esc_html__( "Given string was encrypted using a custom key but 'BACKWPUP_ENC_KEY' constant is not defined anymore.", 'backwpup' )
				);
			}

			return [ BACKWPUP_ENC_KEY, self::KEY_TYPE_CUSTOM ];
		}

		return [ $default_key, '' ];
	}
}
