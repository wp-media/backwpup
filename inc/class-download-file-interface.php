<?php

/**
 * Class BackWPup_Download_File_Interface
 */
interface BackWPup_Download_File_Interface {
	/**
	 * Download File
	 *
	 * Initialize, clean the output and call the function that will download the file
	 *
	 * @return void
	 */
	public function download();

	/**
	 * Set Headers
	 *
	 * @return $this For concatenation
	 */
	public function headers();

	/**
	 * Clean The output
	 *
	 * @return $this For concatenation
	 */
	public function clean_ob();

	/**
	 * File Path
	 *
	 * @return string The file path to download
	 */
	public function filepath();

	/**
	 * Check File Name
	 *
	 * @return $this For concatenation
	 */
	public function check_filename();
}
