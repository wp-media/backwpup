<?php

use Inpsyde\BackWPup\MsAzureDestinationConfiguration;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Blob\Models\GetBlobOptions;
use MicrosoftAzure\Storage\Common\Models\Range;

class BackWPup_Destination_MSAzure_Downloader implements BackWPup_Destination_Downloader_Interface {

	/**
	 * Downloader data object.
	 *
	 * @var BackWpUp_Destination_Downloader_Data
	 */
	private $data;

	/**
	 * Local file handler.
	 *
	 * @var resource
	 */
	private $local_file_handler;

	/**
	 * BackWPup_Destination_MSAzure_Downloader constructor.
	 *
	 * @param BackWpUp_Destination_Downloader_Data $data Download data.
	 */
	public function __construct( BackWpUp_Destination_Downloader_Data $data ) {
		$this->data = $data;
	}

	/**
	 * Download a chunk from Azure storage.
	 *
	 * @param int $start_byte Start byte offset.
	 * @param int $end_byte   End byte offset.
	 *
	 * @return void
	 * @throws RuntimeException When the source is empty or cannot be written.
	 */
	public function download_chunk( $start_byte, $end_byte ) {
		$option = new GetBlobOptions();
		$range  = new Range( $start_byte, $end_byte );
		$option->setRange( $range );

		$client = $this->getBlobClient();

		$blob = $client->getBlob(
			BackWPup_Option::get(
				$this->data->job_id(),
				MsAzureDestinationConfiguration::MSAZURE_CONTAINER
			),
			$this->data->source_file_path(),
			$option
		);

		if ( $blob->getProperties()->getContentLength() === 0 ) {
			throw new RuntimeException(
				esc_html__( 'Could not write data to file. Empty source file.', 'backwpup' )
			);
		}

		$this->setLocalFileHandler( $start_byte );

		$bytes = (int) fwrite( $this->local_file_handler, stream_get_contents( $blob->getContentStream() ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
		if ( 0 === $bytes ) {
			throw new RuntimeException(
				// translators: %s = file name.
				sprintf( esc_html__( 'Could not write data to file %s.', 'backwpup' ), esc_html( $this->data->source_file_path() ) )
			);
		}
	}

	/**
	 * Calculate the total file size.
	 *
	 * @return int
	 */
	public function calculate_size() {
		$client = $this->getBlobClient();

		$blob_properties = $client->getBlobProperties(
			BackWPup_Option::get(
				$this->data->job_id(),
				MsAzureDestinationConfiguration::MSAZURE_CONTAINER
			),
			$this->data->source_file_path()
		);

		return $blob_properties->getProperties()->getContentLength();
	}

	/**
	 * Sets local_file_handler property by opening the current chunk of the resource.
	 *
	 * @param int $start_byte Start byte offset.
	 *
	 * @throws RuntimeException When the local file cannot be opened.
	 */
	private function setLocalFileHandler( $start_byte ) {
		if ( is_resource( $this->local_file_handler ) ) {
			return;
		}

		$this->local_file_handler = fopen( // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
			$this->data->local_file_path(),
			0 === $start_byte ? 'wb' : 'ab'
		);

		if ( ! is_resource( $this->local_file_handler ) ) {
			throw new RuntimeException( esc_html__( 'File could not be opened for writing.', 'backwpup' ) );
		}
	}

	/**
	 * Retrieves the service used to access the blob.
	 *
	 * @return BlobRestProxy
	 */
	private function getBlobClient() {
		$destination = new BackWPup_Destination_MSAzure();

		return $destination->createBlobClient(
			BackWPup_Option::get(
				$this->data->job_id(),
				MsAzureDestinationConfiguration::MSAZURE_ACCNAME
			),
			BackWPup_Encryption::decrypt(
				BackWPup_Option::get(
					$this->data->job_id(),
					MsAzureDestinationConfiguration::MSAZURE_KEY
				)
			)
		);
	}
}
