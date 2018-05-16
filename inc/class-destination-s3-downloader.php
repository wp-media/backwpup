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
class BackWPup_Destination_S3_Downloader implements BackWPup_Destination_Downloader_Interface {

	/**
	 * Capability
	 *
	 * @var string The capability the user should have in order to download the file.
	 */
	private static $capability = 'backwpup_backups_download';

	/**
	 * Service
	 *
	 * @since 3.5.0
	 *
	 * @var mixed Depending on the service. It will be an instance of that class
	 */
	private $service;

	/**
	 * Job ID
	 *
	 * @since 3.5.0
	 *
	 * @var int The job Identifier to use to retrieve the job informations
	 */
	private $job_id;

	/**
	 * File Path
	 *
	 * @since 3.5.0
	 *
	 * @var string From where download the file content
	 */
	private $file_path;

	/**
	 * Destination
	 *
	 * @since 3.5.0
	 *
	 * @var string Where store the file content
	 */
	private $destination;
	
	/**
		 * File handle
		 *
		 * @var resource The file handle for writing.
		 */
	private $file_handle;
	
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
	public function download() {

		if ( ! current_user_can( self::$capability ) ) {
			wp_die( 'Cheatin&#8217; huh?' );
		}

		$file = $this->service->getObject(
			array(
				'Bucket' => BackWPup_Option::get( $this->job_id, 's3bucket' ),
				'Key'    => $this->file_path,
			)
		);

		if ( $file['ContentLength'] > 0 && ! empty( $file['ContentType'] ) ) {
			$body = $file->get( 'Body' );
			$body->rewind();

			$content = '';
			while ( $filedata = $body->read( 1024 ) ) { // phpcs:ignore
				$content .= $filedata;
			}

			backwpup_wpfilesystem()->put_contents( $this->destination, $content );
		}

		if ( ! is_file( $this->destination ) ) {
			throw new \BackWPup_Destination_Download_Exception();
		}

		return $this;
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
				'Range' => 'bytes=' . $startByte . '-' . $endByte,
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
				'scheme'                    => 'https',
				'ssl.certificate_authority' => BackWPup::get_plugin_data( 'cacert' ),
			)
		);

		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function for_job( $job_id ) {

		$this->job_id = $job_id;

		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function from( $file_path ) {

		$this->file_path = $file_path;

		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function to( $destination ) {

		$this->destination = $destination;

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
