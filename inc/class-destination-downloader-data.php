<?php
/**
 * Destination downloader data container.
 */
class BackWpUp_Destination_Downloader_Data {

	/**
	 * Job identifier.
	 *
	 * @var int
	 */
	private $job_id;

	/**
	 * Local file path.
	 *
	 * @var string
	 */
	private $local_file_path;

	/**
	 * Source file path.
	 *
	 * @var string
	 */
	private $source_file_path;

	/**
	 * BackWpUp_Destination_Downloader_Data constructor.
	 *
	 * @param int    $job_id           Job identifier.
	 * @param string $source_file_path Source file path.
	 * @param string $local_file_path  Local file path.
	 */
	public function __construct( $job_id, $source_file_path, $local_file_path ) {
		$this->job_id           = $job_id;
		$this->source_file_path = $source_file_path;
		$this->local_file_path  = $local_file_path;
	}

	/**
	 * Returns the job identifier.
	 *
	 * @return int
	 */
	public function job_id() {
		return $this->job_id;
	}

	/**
	 * Retrieve the local file path where the backup is downloaded.
	 *
	 * @return string
	 */
	public function local_file_path() {
		return $this->local_file_path;
	}

	/**
	 * Retrieve the remote/source file path of the backup.
	 *
	 * @return string
	 */
	public function source_file_path() {
		return $this->source_file_path;
	}
}
