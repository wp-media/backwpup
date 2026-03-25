<?php
/**
 * Encrypt / decrypt data using a trivial character substitution algorithm.
 *
 * This should never be used, really. However, if neither Open SSL or mcrypt are available, we don't really
 * have choice for a better double way encryption.
 * If should consider to require either Open SSL or mcrypt to avoid the usage of this class.
 *
 * Normally we should implement an interface, but at the moment with PHP 5.2 and the "poor" autoloader we have,
 * an interface would just be another file in "/inc" folder causing confusion. Also, I don't want to create an
 * interface in a file named `class-...php`. When we get rid of PHP 5.2, we setup a better autoloader and we get rid of
 * WP coding standard, we finally could consider to introduce an interface.
 */
class BackWPup_Encryption_Fallback {

	public const PREFIX = 'ENC1$';

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
	 * Initialize the fallback encryptor.
	 *
	 * @param string $enc_key  Encryption key.
	 * @param string $key_type Key type identifier.
	 */
	public function __construct( $enc_key, $key_type ) {
		$this->key      = md5( (string) $enc_key );
		$this->key_type = (string) $key_type;
	}

	/**
	 * Check whether fallback encryption is supported.
	 *
	 * @return bool
	 */
	public static function supported() {
		// TODO: Decide whether and how to warn about the security risk.
		return true;
	}

	/**
	 * Encrypt a string (Passwords).
	 *
	 * @param string $value Value to encrypt.
	 *
	 * @return string Encrypted string.
	 */
	public function encrypt( $value ) {
		$result       = '';
		$value_length = strlen( $value );
		$key_length   = strlen( $this->key );

		for ( $i = 0; $i < $value_length; ++$i ) {
			$char     = substr( $value, $i, 1 );
			$key_char = substr( $this->key, ( $i % $key_length ) - 1, 1 );
			$char     = chr( ord( $char ) + ord( $key_char ) );
			$result  .= $char;
		}

		return BackWPup_Encryption::PREFIX . self::PREFIX . $this->key_type . base64_encode( $result ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Binary-safe encoding.
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

		$result           = '';
		$encrypted_length = strlen( $encrypted );
		$key_length       = strlen( $this->key );

		for ( $i = 0; $i < $encrypted_length; ++$i ) {
			$char     = substr( $encrypted, $i, 1 );
			$key_char = substr( $this->key, ( $i % $key_length ) - 1, 1 );
			$char     = chr( ord( $char ) - ord( $key_char ) );
			$result  .= $char;
		}

		return $result;
	}
}
