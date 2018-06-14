<?php
/**
 * Base class for adding BackWPup destinations.
 *
 * @package    BackWPup
 * @subpackage BackWPup_Destinations
 * @since      3.0.0
 * @access private
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

	/**
	 *
	 */
	public function edit_inline_js() {

	}

	/**
	 *
	 */
	public function edit_ajax() {

	}

	/**
	 *
	 */
	public function wizard_admin_print_styles() {

	}

	/**
	 *
	 */
	public function wizard_admin_print_scripts() {

	}

	/**
	 *
	 */
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

	/**
	 *
	 */
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
		$filename   = untrailingslashit( BackWPup::get_plugin_data( 'temp' ) ) . '/' . basename( $local_file_path ?: $file_path );
		$job_id     = filter_var( $_GET['jobid'], FILTER_SANITIZE_NUMBER_INT );

		// Dynamically get downloader class
		$class_name      = get_class( $this );
		$parts           = explode( '_', $class_name );
		$destination     = array_pop( $parts );

		$downloader = new BackWpup_Download_Handler(
			new BackWPup_Download_File(
				$filename,
				mime_content_type( $filename ),
				function ( \BackWPup_Download_File_Interface $obj ) use ( $filename, $file_path, $job_id, $destination ) {

					// Setup Destination service and download file.
					$factory = new BackWPup_Destination_Downloader_Factory( $destination );
					$service = $factory->create();
					$service->for_job( $job_id )
					        ->from( $file_path )
					        ->to( $filename )
					        ->with_service()
					        ->download();

					die();
				},
				$capability
			),
			"download-backup_{$job_id}",
			$capability,
			'download_file'
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
			'.tar.bz2',
			'.tar',
			'.zip',
		);

		$file     = trim( basename( $file ) );
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
	 * Is Backup Owned by Job
	 *
	 * Checks if the given archive belongs to the given job.
	 *
	 * @param string $file
	 * @param int    $jobid
	 *
	 * @return bool
	 */
	public function is_backup_owned_by_job( $file, $jobid ) {

		$info = pathinfo( $file );
		$file = basename( $file, '.' . $info['extension'] );

		// If starts with backwpup, then old-style hash
		$data = array();
		if ( substr( $file, 0, 8 ) == 'backwpup' ) {
			$parts = explode( '_', $file );
			$data  = BackWPup_Option::decode_hash( $parts[1] );
			if ( ! $data ) {
				return false;
			}
		} else {
			// New style, must parse
			// Start at end of file since that's where it is by default

			// Try 10-character chunks first for base 32 and most of base 36
			for ( $i = strlen( $file ) - 10; $i >= 0; $i -- ) {
				$data = BackWPup_Option::decode_hash( substr( $file, $i, 10 ) );
				if ( $data ) {
					break;
				}
			}

			// Try 9-character chunks for any left-over base 36
			if ( ! $data ) {
				for ( $i = strlen( $file ) - 9; $i >= 0; $i -- ) {
					$data = BackWPup_Option::decode_hash( substr( $file, $i, 9 ) );
					if ( $data ) {
						break;
					}
				}
			}

			if ( ! $data ) {
				return false;
			}
		}

		if ( $data[0] != BackWPup::get_plugin_data( 'hash' ) ) {
			return false;
		}

		if ( $data[1] != $jobid ) {
			return false;
		}

		return true;
	}

}
