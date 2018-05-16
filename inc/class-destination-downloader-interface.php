<?php
/**
 * BackWPup_Destination_Downloader_Interface
 *
 * @since   3.5.0
 * @package Inpsyde\BackWPup
 */

/**
 * Class BackWPup_Destination_Downloader_Interface
 *
 * @since   3.5.0
 * @package Inpsyde\BackWPup
 */
interface BackWPup_Destination_Downloader_Interface {

	/**
	 * Download the File
	 *
	 * @since 3.5.0
	 *
	 * @uses wp_die In case the user has not the correct permissions to download the file.
	 *
	 * @throws \BackWPup_Destination_Download_Exception In case the file has not be stored correctly in the folder.
	 *
	 * @return BackWPup_Destination_Downloader_Interface The instance for concatenation
	 */
	public function download();
	
	/**
		 * Download file in chunks
		 *
		 * Given a range of bytes, download that chunk of the file from the Dropbox.
		 *
		 * @param int $startByte The start byte of the range
		 * @param int $endByte   The end byte of the range
		 */
	public function downloadChunk( $startByte, $endByte );

	/**
	 * Job ID Setter
	 *
	 * @since 3.5.0
	 *
	 * @param int $job_id The Job Identifier.
	 *
	 * @return BackWPup_Destination_Downloader_Interface The instance for concatenation
	 */
	public function for_job( $job_id );

	/**
	 * From where Download the File
	 *
	 * @since 3.5.0
	 *
	 * @param string $file_path The path/uri of the file to download.
	 *
	 * @return BackWPup_Destination_Downloader_Interface The instance for concatenation
	 */
	public function from( $file_path );

	/**
	 * Local Destination where Store the File
	 *
	 * @since 3.5.0
	 *
	 * @param string $destination The path where store the file content.
	 *
	 * @return BackWPup_Destination_Downloader_Interface The instance for concatenation
	 */
	public function to( $destination );

	/**
	 * Set and Initialize the Service
	 *
	 * We create the instance of the service and setup it to able to download the content file.
	 * The service depends on the destination used.
	 *
	 * @since 3.5.0
	 *
	 * @return BackWPup_Destination_Downloader_Interface The instance for concatenation
	 */
	public function with_service();
	
	/**
		 * Get the size of the destination file.
		 *
		 * @return int The size of the file.
		 */
	public function getSize();
}
