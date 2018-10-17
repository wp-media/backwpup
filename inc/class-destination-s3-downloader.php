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
final class BackWPup_Destination_S3_Downloader implements BackWPup_Destination_Downloader_Interface {

	const OPTION_BUCKET = 's3bucket';
	const OPTION_ACCESS_KEY = 's3accesskey';
	const OPTION_SECRET_KEY = 's3secretkey';
	const OPTION_REGION = 's3region';

	/**
	 * @var \BackWpUp_Destination_Downloader_Data
	 */
	private $data;

	/**
	 * @var string
	 */
	private $base_url;

	/**
	 * @var Aws\S3\S3Client
	 */
	private $s3_client;

	/**
	 * @var resource
	 */
	private $local_file_handler;

	/**
	 * BackWPup_Destination_S3_Downloader constructor
	 *
	 * @param \BackWpUp_Destination_Downloader_Data $data
	 * @param string                                $base_url
	 */
	public function __construct( BackWpUp_Destination_Downloader_Data $data, $base_url ) {

		$this->data     = $data;
		$this->base_url = $base_url;

		$this->s3_client();
	}

	/**
	 * Clean stuffs
	 */
	public function __destruct() {

		fclose( $this->local_file_handler );
	}

	/**
	 * @inheritdoc
	 */
	public function download_chunk( $start_byte, $end_byte ) {

		$file = $this->s3_client->getObject( array(
			'Bucket' => BackWPup_Option::get( $this->data->job_id(), self::OPTION_BUCKET ),
			'Key'    => $this->data->source_file_path(),
			'Range'  => 'bytes=' . $start_byte . '-' . $end_byte,
		) );

		if ( empty( $file['ContentType'] ) || $file['ContentLength'] === 0 ) {
			throw new \RuntimeException( __( 'Could not write data to file. Empty source file.', 'backwpup' ) );
		}

		$this->local_file_handler( $start_byte );

		$bytes = (int) fwrite( $this->local_file_handler, (string) $file['Body'] );
		if ( $bytes === 0 ) {
			throw new \RuntimeException( __( 'Could not write data to file.', 'backwpup' ) );
		}
	}

	/**
	 * @inheritdoc
	 */
	public function calculate_size() {

		$file = $this->s3_client->getObject( array(
			'Bucket' => BackWPup_Option::get( $this->data->job_id(), self::OPTION_BUCKET ),
			'Key'    => $this->data->source_file_path(),
		) );

		return (int) ( ! empty( $file['ContentType'] ) ? $file['ContentLength'] : 0 );
	}

	/**
	 * @param int $start_byte
	 */
	private function local_file_handler( $start_byte ) {

		if ( is_resource( $this->local_file_handler ) ) {
			return;
		}

		$this->local_file_handler = fopen( $this->data->local_file_path(), $start_byte == 0 ? 'wb' : 'ab' );

		if ( ! is_resource( $this->local_file_handler ) ) {
			throw new \RuntimeException( __( 'File could not be opened for writing.', 'backwpup' ) );
		}
	}

	/**
	 * Build S3 Client
	 */
	private function s3_client() {

		if ( $this->s3_client ) {
			return;
		}

		$secret_key = BackWPup_Option::get( $this->data->job_id(), self::OPTION_SECRET_KEY );

		$this->s3_client = Aws\S3\S3Client::factory(
			array(
				'signature'                 => 'v4',
				'key'                       => BackWPup_Option::get( $this->data->job_id(), self::OPTION_ACCESS_KEY ),
				'secret'                    => BackWPup_Encryption::decrypt( $secret_key ),
				'region'                    => BackWPup_Option::get( $this->data->job_id(), self::OPTION_REGION ),
				'base_url'                  => $this->base_url,
				'scheme'                    => 'https',
				'ssl.certificate_authority' => BackWPup::get_plugin_data( 'cacert' ),
			)
		);
	}
}
