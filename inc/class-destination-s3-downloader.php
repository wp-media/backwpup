<?php
/**
 * S3 Downloader
 *
 * @since   3.5.0
 * @package Inpsyde\BackWPup
 */

/**
 * Class BackWPup_Destination_S3_Downloader
 *
 * @since   3.5.0
 * @package Inpsyde\BackWPup
 */
class BackWPup_Destination_S3_Downloader extends BackWPup_Destination_Downloader {

	/**
	 * File handle
	 *
	 * @var resource The file handle for writing.
	 */
	private $file_handle;

	private $base_url;

	public function __construct( $base_url = '' ) {

		$this->base_url = $base_url;
	}

	/**
	 * Closes the file handle
	 */
	public function __destruct() {

		if ( $this->file_handle ) {
			fclose( $this->file_handle );
		}
	}

	/**
	 * @inheritdoc
	 */
	public function downloadChunk( $startByte, $endByte ) {

		if ( ! current_user_can( self::$capability ) ) {
			wp_die( 'Cheatin&#8217; huh?' );
		}

		$file = $this->service->getObject(
			array(
				'Bucket' => BackWPup_Option::get( $this->job_id, 's3bucket' ),
				'Key'    => $this->file_path,
				'Range'  => 'bytes=' . $startByte . '-' . $endByte,
			)
		);

		if ( $file['ContentLength'] > 0 && ! empty( $file['ContentType'] ) ) {
			if ( ! $this->file_handle ) {
				$this->file_handle = fopen( $this->destination, $startByte == 0 ? 'wb' : 'ab' );

				if ( $this->file_handle === false ) {
					throw new \RuntimeException( __( 'File could not be opened for writing.', 'backwpup' ) );
				}
			}

			$bytes = fwrite( $this->file_handle, (string) $file['Body'] );
			if ( $bytes === false ) {
				throw new \RuntimeException( __( 'Could not write data to file.', 'backwpup' ) );
			}
		}

	}

	/**
	 * @inheritdoc
	 */
	public function with_service() {

		$this->service = Aws\S3\S3Client::factory(
			array(
				'signature'                 => 'v4',
				'key'                       => BackWPup_Option::get( $this->job_id, 's3accesskey' ),
				'secret'                    => BackWPup_Encryption::decrypt(
					BackWPup_Option::get( $this->job_id, 's3secretkey' )
				),
				'region'                    => BackWPup_Option::get( $this->job_id, 's3region' ),
				'base_url'                  => $this->base_url,
				'scheme'                    => 'https',
				'ssl.certificate_authority' => BackWPup::get_plugin_data( 'cacert' ),
			)
		);

		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function getSize() {

		$object = $this->service->getObject(
			array(
				'Bucket' => BackWPup_Option::get( $this->job_id, 's3bucket' ),
				'Key'    => $this->file_path,
			)
		);

		return (int) $object['ContentLength'];
	}
}
