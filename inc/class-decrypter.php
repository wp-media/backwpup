<?php
/**
 * BackWPup_Decrypter
 *
 * @since   3.6.0
 * @author  Brandon Olivares
 * @package Inpsyde\BackWPup
 */

use phpseclib\Crypt\AES;
use phpseclib\Crypt\RSA;

/**
 * BackWPup_Decrypter
 *
 * Decrypt backup archives using AES or RSA.
 *
 * @since   3.6.0
 * @author  Brandon Olivares
 * @package Inpsyde\BackWPup
 */
class BackWPup_Decrypter {

	const PRIVATE_KEY_STATUS_OK = 'ok';
	const PRIVATE_KEY_STATUS_INVALID = 'invalid';
	const PRIVATE_KEY_STATUS_NOT_FOUND = 'not-found';
	const PUBLIC_KEY_OPTION = 'backwpup_cfg_publickey';
	const ENCRYPTION_KEY_OPTION = 'backwpup_cfg_encryptionkey';
	const PRIVATE_RSA_ID_FILE = 'id_rsa_backwpup.pri';

	/**
	 * @var
	 */
	private $local_file_path;

	/**
	 * BackWPup_Decrypter constructor
	 *
	 * @param $local_file_path
	 */
	public function __construct( $local_file_path ) {

		$this->local_file_path = $local_file_path;
	}

	/**
	 * @return bool
	 * @throws \Exception
	 */
	public function decrypt() {

		$aes                 = new AES( AES::MODE_CBC );
		$source_file_handler = fopen( $this->local_file_path, 'rb' );

		if ( ! is_resource( $source_file_handler ) ) {
			throw new \Exception( __( 'Cannot open the archive for reading.', 'backwpup' ) );
		}

		// Read first byte to know what encryption method was used
		$key  = '';
		$type = fread( $source_file_handler, 1 );

		// Symmetric mode
		if ( $type === chr( 0 ) ) {
			$key = pack( 'H*', get_site_option( self::ENCRYPTION_KEY_OPTION ) );
		}
		// Asymmetric mode
		if ( $type === chr( 1 ) ) {
			$key = $this->get_rsa_decrypted_key( $source_file_handler );
		}

		if ( $key === '' ) {
			return false;
		}

		if ( file_exists( $this->local_file_path . '.encrypted' ) ) {
			unlink( $this->local_file_path . '.encrypted' );
		}

		$local_file_handler = fopen( $this->local_file_path . '.encrypted', 'a+b' );
		if ( ! is_resource( $local_file_handler ) ) {
			throw new \Exception( __( 'Cannot write the encrypted archive.', 'backwpup' ) );
		}

		$aes->setKey( $key );
		$aes->enableContinuousBuffer();
		$aes->disablePadding();

		$block_size = 128 * 1024;
		$bytes_read = 0;

		while ( ! feof( $source_file_handler ) ) {
			$data       = fread( $source_file_handler, $block_size );
			$packet     = $aes->decrypt( $data );
			$bytes_read += strlen( $data );

			if ( feof( $source_file_handler ) ) {
				// This is the last chunk, so strip padding
				$padding_length = ord( $packet[ strlen( $packet ) - 1 ] );
				if ( $padding_length <= 16 ) {
					$packet = substr( $packet, 0, - $padding_length );
				}
			}
			fwrite( $local_file_handler, $packet );
		}

		$file_in  = null;
		$file_out = null;

		unlink( $this->local_file_path );
		rename( $this->local_file_path . '.encrypted', $this->local_file_path );

		return true;
	}

	/**
	 * @param $source_file_handler
	 *
	 * @return bool|string
	 */
	private function get_rsa_decrypted_key( $source_file_handler ) {

		$rsa              = new RSA();
		$private_key      = '';
		$verified         = false;
		$status           = self::PRIVATE_KEY_STATUS_NOT_FOUND;
		$length           = unpack( 'H*', fread( $source_file_handler, 1 ) );
		$length           = hexdec( $length[1] );
		$key              = fread( $source_file_handler, $length );
		$private_key_file = dirname( $this->local_file_path ) . '/' . self::PRIVATE_RSA_ID_FILE;

		if ( ! file_exists( $private_key_file ) ) {
			self::send_message( array(
				'state'  => 'need-private-key',
				'status' => $status,
			) );

			return '';
		}

		if ( file_exists( $private_key_file ) ) {
			$private_key = file_get_contents( $private_key_file );
			unlink( $private_key_file );

			$verified = $this->verify_private_key( $rsa, $private_key );
		}

		if ( ! $verified ) {
			self::send_message( array(
				'state'  => 'need-private-key',
				'status' => self::PRIVATE_KEY_STATUS_INVALID,
			) );

			return '';
		}

		$private_key and $rsa->loadKey( $private_key );

		$key = $rsa->decrypt( $key );

		if ( $key === '' ) {
			throw new \RuntimeException( __( 'Private key invalid.', 'backwpup' ) );
		}

		return $key;
	}

	/**
	 * @param \phpseclib\Crypt\RSA $rsa
	 * @param                      $private_key
	 *
	 * @return bool
	 */
	private function verify_private_key( RSA $rsa, $private_key ) {

		$rsa->setSignatureMode( RSA::SIGNATURE_PKCS1 );

		if ( ! $rsa->loadKey( $private_key ) ) {
			return false;
		}

		$signature = $rsa->sign( 'test' );
		$rsa->loadKey( get_site_option( self::PUBLIC_KEY_OPTION ) );

		return $rsa->verify( 'test', $signature );
	}

	/**
	 * @param        $data
	 * @param string $event
	 */
	private static function send_message( $data, $event = 'message' ) {

		echo "event: {$event}\n";
		echo "data: " . wp_json_encode( $data ) . "\n\n";
		flush();
	}
}
