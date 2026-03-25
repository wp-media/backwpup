<?php
/**
 * Destination downloader interface.
 */

/**
 * Interface BackWPup_Destination_Downloader_Service_Interface.
 */
interface BackWPup_Destination_Downloader_Interface {

	/**
	 * Download part of the backup file.
	 *
	 * @param int $start_byte Start byte offset.
	 * @param int $end_byte   End byte offset.
	 *
	 * @throws Exception In case something went wrong.
	 */
	public function download_chunk( $start_byte, $end_byte );

	/**
	 * Calculated the size of the source file.
	 *
	 * @return int
	 */
	public function calculate_size();
}
