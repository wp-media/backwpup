<?php

/**
 * Base class for adding BackWPup destinations.
 *
 * @package BackWPup
 * @since 3.0.0
 */
abstract class BackWPup_Destinations {

	/**
	 * @return array
	 */
	abstract public function option_defaults();

	/**
	 * @param $jobid int
	 */
	abstract public function edit_tab( $jobid );

	/**
	 * @param $jobid int
	 */
	public function edit_auth( $jobid ) {

	}

	/**
	 * @param $jobid int
	 */
	abstract public function edit_form_post_save( $jobid );

	/**
	 * use wp_enqueue_script() here to load js for tab
	 */
	public function admin_print_scripts() {

	}

	public function edit_inline_js() {

	}

	public function edit_ajax() {

	}

	public function wizard_admin_print_styles() {

	}

	public function wizard_admin_print_scripts() {

	}

	public function wizard_inline_js() {

	}

	/**
	 * @param $job_settings array
	 */
	public function wizard_page( array $job_settings ) {

		echo '<br /><pre>';
		print_r( $job_settings );
		echo '</pre>';
	}

	/**
	 * @param $job_settings array
	 *
	 * @return array
	 */
	public function wizard_save( array $job_settings ) {

		return $job_settings;
	}

	public function admin_print_styles() {

	}

	/**
	 * @param $jobdest string
	 * @param $backupfile
	 */
	public function file_delete( $jobdest, $backupfile ) {

	}

	/**
	 * @param $jobid int
	 * @param $file_path
	 * @param $local_file_path
	 */
	public function file_download( $jobid, $file_path, $local_file_path = null ) {

		$capability = 'backwpup_backups_download';
		$filename = untrailingslashit( BackWPup::get_plugin_data( 'temp' ) ) . '/' . basename( $local_file_path ?: $file_path );
		$job_id = filter_var( $_GET['jobid'], FILTER_SANITIZE_NUMBER_INT );

		// Dynamically get downloader class
		$class_name = get_class( $this );
		$parts = explode( '_', $class_name );
		$destination = array_pop( $parts );

		$downloader = new BackWpup_Download_Handler(
			new BackWPup_Download_File(
				$filename,
				function ( \BackWPup_Download_File_Interface $obj ) use (
					$filename,
					$file_path,
					$job_id,
					$destination
				) {

					// Setup Destination service and download file.
					$factory = new BackWPup_Destination_Downloader_Factory();
					$downloader = $factory->create(
						$destination,
						$job_id,
						$file_path,
						$filename
					);
					$downloader->download_by_chunks();
					die();
				},
				$capability
			),
			'backwpup_action_nonce',
			$capability,
			'download_backup_file'
		);

		// Download the file.
		$downloader->handle();
	}

	/**
	 * @param $jobdest string
	 *
	 * @return array
	 */
	public function file_get_list( $jobdest ) {

		return array();
	}

	/**
	 * @param $job_object BackWPup_Job
	 */
	abstract public function job_run_archive( BackWPup_Job $job_object );

	/**
	 * @param $job_object BackWPup_Job
	 */
	public function job_run_sync( BackWPup_Job $job_object ) {

	}

	/**
	 * Prepare Restore
	 *
	 * Method for preparing the restore process.
	 *
	 * @param $job_id    int    Number of job.
	 * @param $file_name string Name of backup.
	 *
	 * @return string The file path, empty string if file cannot be found.
	 */
	public function prepare_restore( $job_id, $file_name ) {

	}

	/**
	 * @param $job_settings array
	 *
	 * @return bool
	 */
	abstract public function can_run( array $job_settings );

	/**
	 * Is Backup Archive
	 *
	 * Checks if given file is a backup archive.
	 *
	 * @param $file
	 *
	 * @return bool
	 */
	public function is_backup_archive( $file ) {

		$extensions = array(
			'.tar.gz',
			'.tar',
			'.zip',
		);

		$file = trim( basename( $file ) );
		$filename = '';

		foreach ( $extensions as $extension ) {
			if ( substr( $file, ( strlen( $extension ) * - 1 ) ) === $extension ) {
				$filename = substr( $file, 0, ( strlen( $extension ) * - 1 ) );
			}
		}

		if ( ! $filename ) {
			return false;
		}

		return true;
	}

    /**
     * Checks if the given archive belongs to the given job.
     *
     * @param string $file
     * @param int $jobid
     *
     * @return bool
     */
    public function is_backup_owned_by_job($file, $jobid)
    {

        $info = pathinfo($file);
        $file = basename($file, '.' . $info['extension']);

        // Try 10-character chunks first for base 32 and most of base 36
        $data = $this->getDecodedHashAndJobId($file, 10);

        // Try 9-character chunks for any left-over base 36
        if (!$data) {
            $data = $this->getDecodedHashAndJobId($file, 9);
        }

        if (!$data || !$this->dataContainsCorrectValues($data, $jobid)) {
            return false;
        }

        return true;
    }

    /**
     * @param string $file
     * @param int $numberOfCharacters
     *
     * @return array|bool
     */
    protected function getDecodedHashAndJobId($file, $numberOfCharacters)
    {

        $data = array();

        for ($i = strlen($file) - $numberOfCharacters; $i >= 0; $i--) {
            $data = BackWPup_Option::decode_hash(substr($file, $i, $numberOfCharacters));
            if ($data) {
                break;
            }
        }

        return $data;
    }

    /**
     * @param array $data
     * @param int $jobid
     *
     * @return bool
     */
    protected function dataContainsCorrectValues($data, $jobid)
    {

        if ($data[0] !== BackWPup::get_plugin_data('hash')) {
            return false;
        }

        if ($data[1] !== $jobid) {
            return false;
        }

        return true;
    }
}
