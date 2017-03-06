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

	const PREFIX = '$BackWPup$';
	const KEY_TYPE_CUSTOM = '$0';

	private static $classes = array(
		BackWPup_Encryption_OpenSSL::PREFIX  => 'BackWPup_Encryption_OpenSSL',
		BackWPup_Encryption_Mcrypt::PREFIX   => 'BackWPup_Encryption_Mcrypt',
		BackWPup_Encryption_Fallback::PREFIX => 'BackWPup_Encryption_Fallback',
	);

	/**
	 *
	 * Encrypt a string using the best algorithm available.
	 *
	 * In case the given string is encrypted with a weaker algorithm, it will first be decrypted then the plain text
	 * obtained is encrypted with the better algorithm available and returned.
	 *
	 * @param string $string value to encrypt
	 *
	 * @return string encrypted string
	 */
	public static function encrypt( $string ) {

		if ( ! is_string( $string ) || ! $string ) {
			return '';
		}

		try {
			$cypher_class = self::cypher_class_for_string( $string );
		}
		catch ( Exception $e ) {

			/** @TODO what to do here? The string is encrypted, but cypher used to encrypt isn't supported in current system */

			return $string;
		}

		try {
			list( $key, $key_type ) = self::get_encrypt_info( $cypher_class, $string );
		}
		catch ( Exception $e ) {

			/** @TODO what to do here? The string is encrypted, a custom key was used to encrypt, but it is not available anymore */

			return $string;
		}

		$best_cipher_class = self::best_cypher();

		// The given string is not encrypted, let's encrypt it and return
		if ( ! $cypher_class ) {

			/** @var BackWPup_Encryption_OpenSSL|BackWPup_Encryption_Mcrypt|BackWPup_Encryption_Fallback $best_cypher */
			$best_cypher = new $best_cipher_class( $key, $key_type );

			return $best_cypher->encrypt( $string );
		}

		$encryption_count = substr_count( $string, self::PREFIX );

		// The given string is encrypted once using best cypher, let's just return it
		if ( $encryption_count === 1 && $cypher_class === $best_cipher_class ) {
			return $string;
		}

		/** @var BackWPup_Encryption_OpenSSL|BackWPup_Encryption_Mcrypt|BackWPup_Encryption_Fallback $cypher */
		$cypher = new $cypher_class( $key, $key_type );

		$string = $cypher->decrypt( $string );

		return self::encrypt( $string );
	}

	/**
	 *
	 * Decrypt a string (Passwords)
	 *
	 * @param string $string value to decrypt
	 *
	 * @return string decrypted string
	 */
	public static function decrypt( $string ) {

		if ( ! is_string( $string ) || ! $string ) {
			return '';
		}

		try {
			$cypher_class = self::cypher_class_for_string( $string );
		}
		catch ( Exception $e ) {

			/** @TODO what to do here? The cypher used to encrypt is not supported in current system */

			return '';
		}

		if ( ! $cypher_class ) {

			/** @TODO what to do here? The string seems not encrypted or maybe is corrupted */

			return $string;
		}

		try {
			list( $key, $key_type ) = self::get_encrypt_info( $cypher_class, $string );
		}
		catch ( Exception $e ) {

			/** @TODO what to do here? A custom key was used to encrypt but it is not available anymore */
			return '';
		}

		/** @var BackWPup_Encryption_OpenSSL|BackWPup_Encryption_Mcrypt|BackWPup_Encryption_Fallback $cypher */
		$cypher = new $cypher_class( $key, $key_type );

		return trim( $cypher->decrypt( $string ), "\0" );
	}

	/**
	 * @param string $string
	 *
	 * @return string
	 * @throws Exception
	 */
	private static function cypher_class_for_string( $string ) {

		foreach ( self::$classes as $prefix => $class ) {

			$enc_prefix = self::PREFIX . $prefix;

			if ( strpos( $string, $enc_prefix ) !== 0 ) {
				continue;
			}

			if ( ! call_user_func( array( $class, 'supported' ) ) ) {
				throw new Exception(
					"Give string was encrypted using {$class} but it is not currently supported in this system."
				);
			}

			return $class;
		}

		return '';
	}

	/**
	 * @return string
	 */
	private static function best_cypher() {

		foreach ( self::$classes as $prefix => $class ) {

			if ( ! call_user_func( array( $class, 'supported' ) ) ) {
				continue;
			}

			return $class;
		}

		// This should never happen because BackWPup_Encryption_Fallback::supported() always returns true

		return '';
	}

	/**
	 * @param string|null $class
	 * @param string      $string
	 *
	 * @return array
	 * @throws Exception
	 */
	private static function get_encrypt_info( $class = null, $string = '' ) {

		$default_key = DB_NAME . DB_USER . DB_PASSWORD;

		if ( ! is_string( $class ) || ! $class ) {
			return defined( 'BACKWPUP_ENC_KEY' )
				? array( BACKWPUP_ENC_KEY, self::KEY_TYPE_CUSTOM )
				: array( $default_key, '' );
		}

		$enc_prefix     = self::PREFIX . constant( "{$class}::PREFIX" );
		$has_custom_key = strpos( $string, $enc_prefix . self::KEY_TYPE_CUSTOM ) === 0;

		if ( $has_custom_key && ! defined( 'BACKWPUP_ENC_KEY' ) ) {
			throw new Exception(
				"Give string was encrypted using a custom key but 'BACKWPUP_ENC_KEY' constant is not defined anymore."
			);
		}

		if ( $has_custom_key ) {
			return array( BACKWPUP_ENC_KEY, self::KEY_TYPE_CUSTOM );
		}

		return array( $default_key, '' );
	}
}
