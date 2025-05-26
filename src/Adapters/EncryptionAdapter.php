<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Adapters;

class EncryptionAdapter {
	/**
	 * Decrypt a string (Passwords).
	 *
	 * @param string $data The value to decrypt.
	 * @return string Decrypted string.
	 */
	public function decrypt( string $data ) {
		return \BackWPup_Encryption::decrypt( $data );
	}

	/**
	 * Encrypts the given data using the BackWPup encryption mechanism.
	 *
	 * @param string $data The data to be encrypted.
	 * @return string The encrypted data.
	 */
	public function encrypt( string $data ): string {
		return \BackWPup_Encryption::encrypt( $data );
	}
}
