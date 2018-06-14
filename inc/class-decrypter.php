<?php
/**
 * BackWPup_Decrypter
 *
 * @since 3.6.0
 * @author Brandon Olivares
 * @package Inpsyde\BackWPup
 */

use phpseclib\Crypt\AES;
use phpseclib\Crypt\RSA;

/**
 * BackWPup_Decrypter
 *
 * Decrypt backup archives using AES or RSA.
 *
 * @since 3.6.0
 * @author Brandon Olivares
 * @package Inpsyde\BackWPup
 */
class BackWPup_Decrypter {

	/**
	 * File Path
	 *
	 * @var string The path to the file to decrypt
	 */
	private $file_path;

	/**
	 * Constructor
	 *
	 * @param string $file_path Path to the file to decrypt
	 */
	public function __construct( $file_path ) {

		$this->file_path = $file_path;
	}

	/**
	 * Decrypt
	 *
	 * Decrypts the archive.
	 */
	public function decrypt() {

		$aes = new AES( AES::MODE_CBC );

		try {
			$file_in = new \SplFileObject( $this->file_path, 'rb' );
		} catch ( \RuntimeException $e ) {
			throw new \Exception( __( 'Cannot open the archive for reading.', 'backwpup' ) );
		}

		// Read first byte to know what encryption method was used
		$key = null;
		$type = $file_in->fread( 1 );
		if ( $type == chr( 0 ) ) {
			// Symmetric mode
			$key = pack( 'H*', get_site_option( 'backwpup_cfg_encryptionkey' ) );
		} elseif ( $type == chr( 1 ) ) {
			// Asymmetric mode
			$key = $this->getRSADecryptedKey( $file_in );
		} else {
			// Neither, which means it's probably not encrypted
			return false;
		}

		if ( file_exists( $this->file_path . '.encrypted' ) ) {
			unlink( $this->file_path . '.encrypted' );
		}

		try {
			$file_out = new \SplFileObject( $this->file_path . '.encrypted', 'a+b' );
		} catch ( \RuntimeException $e ) {
			throw new \Exception( __( 'Cannot write the encrypted archive.', 'backwpup' ) );
		}

		$aes->setKey( $key );
		$aes->enableContinuousBuffer();
		$aes->disablePadding();

		$block_size = 128 * 1024;
		$bytes_read = 0;
		$size       = $file_in->getSize();
		// Subtract the number of bytes we've read into $file_in
		// This is to make up for the overhead of the data we've stored in the encrypted file
		// $size - overhead = the actual encrypted file
		$size -= $file_in->ftell();

		while ( $file_in->valid() ) {
			$data = $file_in->fread( $block_size );
			$packet = $aes->decrypt( $data );
			$bytes_read += strlen( $data );

			if ( $file_in->eof() ) {
				// This is the last chunk, so strip padding
				$padding_length = ord( $packet[ strlen( $packet ) - 1 ] );
				if ( $padding_length <= 16 ) {
					$packet = substr( $packet, 0, -$padding_length );
				}
			}
			$file_out->fwrite( $packet );
		}

		$file_in = null;
		$file_out = null;

		unlink( $this->file_path );
		rename( $this->file_path . '.encrypted', $this->file_path );

		return true;
	}

	/**
	 * Get RSA Decrypted Key
	 *
	 * Reads and decrypts the generated AES key at the start of the archive.
	 *
	 * This key is RSA-encrypted.
	 *
	 * @param SplFileObject $file_in The file to read from
	 *
	 * @return string The decrypted AES key.
	 */
	private function getRSADecryptedKey( \SplFileObject $file_in ) {

		// The next byte is the length of the encrypted key
		$length = unpack( 'H*', $file_in->fread( 1 ) );
		$length = hexdec($length[1]);

		// Read $length bytes to get encrypted key
		$key = $file_in->fread( $length );

		// Decrypt
		$rsa = new RSA();

		// Check for private key file
		$key_filename = dirname( $this->file_path ) . '/id_rsa_backwpup.pri';
		$status = 'not-found';
		while ( $status != 'ok' ) {
			if ( ! file_exists( $key_filename ) ) {
				BackWPup_Destination_Downloader::sendMessage( array(
					'state'  => 'need-private-key',
					'status' => $status,
				) );

				// Loop until we see the private key
				do {
					sleep( 5 );
				} while ( ! file_exists( $key_filename ) );
			}

			$private_key = file_get_contents( $key_filename );
			unlink( $key_filename );

			// Verify the private key is correct
			$rsa->setSignatureMode( RSA::SIGNATURE_PKCS1 );

			// Load private key
			$rsa->loadKey( $private_key );

			// Get signature
			$signature = $rsa->sign( 'test' );

			// And verify signature
			$rsa->loadKey( get_site_option( 'backwpup_cfg_publickey' ) );
			$verified = $rsa->verify( 'test', $signature );

			if ( $verified) {
				$status = 'ok';
			} else {
				$status = 'invalid';
			}
		}

		$rsa->loadKey( $private_key );
		$key = $rsa->decrypt( $key );

		if ( ! $key ) {
			throw new \Exception( __( 'Private key invalid.', 'backwpup' ) );
		}

		return $key;
	}

}
