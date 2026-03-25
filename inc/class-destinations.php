<?php

/**
 * Base class for adding BackWPup destinations.
 *
 * @since 3.0.0
 */
abstract class BackWPup_Destinations {

	/**
	 * Download capability name.
	 *
	 * @var string
	 */
	private const CAPABILITY = 'backwpup_backups_download';
	/**
	 * Backup archive extensions.
	 *
	 * @var string[]
	 */
	private const EXTENSIONS = [
		'.tar.gz',
		'.tar',
		'.zip',
	];
	/**
	 * The local destination.
	 *
	 * @var array
	 */
	private static $local_destination = [
		'local' => [
			'slug'  => 'FOLDER',
			'label' => 'Website Server',
		],
	];
	/**
	 * The cloud destinations.
	 *
	 * @var array
	 */
	private static $destinations = [
		'dropbox'   => [
			'slug'  => 'DROPBOX',
			'label' => 'Dropbox',
		],
		'sftp'      => [
			'slug'  => 'FTP',
			'label' => 'FTP',
		],
		'msazure'   => [
			'slug'  => 'MSAZURE',
			'label' => 'Microsoft Azure',
		],
		's3'        => [
			'slug'  => 'S3',
			'label' => 'Amazon S3',
		],
		'sugarsync' => [
			'slug'  => 'SUGARSYNC',
			'label' => 'Sugar Sync',
		],
		'rsc'       => [
			'slug'  => 'RSC',
			'label' => 'Rackspace Cloud',
		],
	];

	/**
	 * Get default options for the destination.
	 *
	 * @return array
	 */
	abstract public function option_defaults(): array;

	/**
	 * Edit authentication settings.
	 *
	 * @param int $jobid Job id.
	 *
	 * @return void
	 */
	public function edit_auth( int $jobid ): void {
	}

	/**
	 * Save the form data.
	 *
	 * @param int|array $jobid Job id or list of ids.
	 *
	 * @return void
	 */
	abstract public function edit_form_post_save( $jobid ): void;

	/**
	 * Use wp_enqueue_script() here to load JS for tab.
	 *
	 * @return void
	 */
	public function admin_print_scripts(): void {
	}

	/**
	 * Print inline JavaScript.
	 *
	 * @return void
	 */
	public function edit_inline_js(): void {
	}

	/**
	 * Handle AJAX for destination settings.
	 *
	 * @return void
	 */
	public function edit_ajax(): void {
	}

	/**
	 * Enqueue admin styles.
	 *
	 * @return void
	 */
	public function admin_print_styles(): void {
	}

	/**
	 * Delete a file from the destination.
	 *
	 * @param string $jobdest    Destination identifier.
	 * @param string $backupfile Backup file path.
	 *
	 * @return void
	 */
	public function file_delete( string $jobdest, string $backupfile ): void {
	}

	/**
	 * Download a file from the destination.
	 *
	 * @param int         $jobid           Job id.
	 * @param string      $file_path       Remote file path.
	 * @param string|null $local_file_path Local file path.
	 *
	 * @return void
	 */
	public function file_download( int $jobid, string $file_path, ?string $local_file_path = null ): void {
		$filename = untrailingslashit( BackWPup::get_plugin_data( 'temp' ) ) . '/' . basename( $local_file_path ?: $file_path );

		// Dynamically get downloader class.
		$class_name  = get_class( $this );
		$parts       = explode( '_', $class_name );
		$destination = array_pop( $parts );

		$downloader = new BackWpup_Download_Handler(
			new BackWPup_Download_File(
				$filename,
				static function () use (
					$filename,
					$file_path,
					$jobid,
					$destination
				): void {
					// Setup Destination service and download file.
					$factory    = new BackWPup_Destination_Downloader_Factory();
					$downloader = $factory->create(
						$destination,
						$jobid,
						$file_path,
						$filename
					);
					$downloader->download_by_chunks();

					exit();
				},
				self::CAPABILITY
			),
			'backwpup_action_nonce',
			self::CAPABILITY,
			'download_backup_file'
		);

		// Download the file.
		$downloader->handle();
	}

	/**
	 * Get the list of files for the job.
	 *
	 * @param string $jobdest The job destination identifier.
	 * @return array
	 */
	public function file_get_list( string $jobdest ): array {
		$key  = 'backwpup_' . strtolower( $jobdest );
		$list = get_site_transient( $key );

		if ( false === $list ) {
			// Legacy compatibility (e.g. Glacier history stored in options).
			$list = get_site_option( $key, [] );
		}

		$files = array_filter( (array) $list );

		// Disable auto downloading for onedrive during restoration. See #1239 on Github.
		if ( BackWPup::is_pro() && $this->get_service_name() !== 'OneDrive' ) {
			$file_list = new BackWPup_Pro_Destinations();

			return $file_list->file_get_list( $jobdest, $files, $this->get_service_name() );
		}

		return $files;
	}

	/**
	 * Run the archive job for destination.
	 *
	 * @param BackWPup_Job $job_object Job object.
	 *
	 * @return bool
	 */
	abstract public function job_run_archive( BackWPup_Job $job_object ): bool;

	/**
	 * Run the sync job for destination.
	 *
	 * @param BackWPup_Job $job_object Job object.
	 *
	 * @return bool
	 */
	public function job_run_sync( BackWPup_Job $job_object ): bool {
		unset( $job_object );

		return true;
	}

	/**
	 * Check whether destination can run.
	 *
	 * @param array $job_settings Job settings.
	 *
	 * @return bool
	 */
	abstract public function can_run( array $job_settings ): bool;

	/**
	 * Is Backup Archive.
	 *
	 * Checks if given file is a backup archive.
	 *
	 * @param string $file File path.
	 *
	 * @return bool
	 */
	public function is_backup_archive( string $file ): bool {
		$filename = trim( basename( $file ) );
		foreach ( self::EXTENSIONS as $extension ) {
			if ( substr( $filename, ( strlen( $extension ) * -1 ) ) === $extension ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks if the given archive belongs to the given job.
	 *
	 * @param string $file  File path.
	 * @param int    $jobid Job id.
	 *
	 * @return bool
	 */
	public function is_backup_owned_by_job( string $file, int $jobid ): bool {
		if ( ! $this->is_backup_archive( $file ) ) {
			return false;
		}

		$file = basename( $file );
		$file = str_ireplace( self::EXTENSIONS, '', $file );

		// Try 10-character chunks first for base 32 and most of base 36.
		$data = $this->get_decoded_hash_and_job_id( $file, 10 );

		// Try 9-character chunks for any left-over base 36.
		if ( ! $data ) {
			$data = $this->get_decoded_hash_and_job_id( $file, 9 );
		}

		return $data && $this->data_contains_correct_values( $data, $jobid );
	}

	/**
	 * Decode hash and job id from the file name.
	 *
	 * @param string $file                 File name.
	 * @param int    $number_of_characters Number of characters to decode.
	 *
	 * @return array|bool
	 */
	protected function get_decoded_hash_and_job_id( string $file, int $number_of_characters ) {
		$data = [];

		for ( $i = strlen( $file ) - $number_of_characters; $i >= 0; --$i ) {
			$data = BackWPup_Option::decode_hash( substr( $file, $i, $number_of_characters ) );
			if ( $data ) {
				break;
			}
		}

		return $data;
	}

	/**
	 * Check if decoded data matches expected values.
	 *
	 * @param array $data  Decoded data.
	 * @param int   $jobid Job id.
	 *
	 * @return bool
	 */
	protected function data_contains_correct_values( array $data, int $jobid ): bool {
		if ( BackWPup::get_plugin_data( 'hash' ) !== $data[0] ) {
			return false;
		}

		return $data[1] === $jobid;
	}

	/**
	 * Get the storage destinations list.
	 *
	 * @param bool $with_array Include the local destination.
	 * @return array
	 */
	public static function get_destinations( bool $with_array = false ): array {
		$destinations = [];
		if ( $with_array ) {
			$destinations = array_merge( self::$local_destination, self::$destinations );
		} else {
			$destinations = self::$destinations;
		}
		if ( BackWPup::is_pro() ) {
			$destinations = array_merge( $destinations, BackWPup_Pro_Destinations::get_destinations() );
		}

		$checked_destinations = \BackWPup::get_registered_destinations();
		foreach ( $destinations as $key => $destination ) {
			$error = $checked_destinations[ $destination['slug'] ]['error'];
			if ( $error ) {
				$destinations[ $key ]['deactivated_message'] = $error;
			}
		}

		return $destinations;
	}

	/**
	 * Remove file history from database
	 *
	 * @param array  $files Array of files that should be removed.
	 * @param string $destination The destination of the backup.
	 *
	 * @return void
	 */
	public function remove_file_history_from_database( array $files, string $destination ): void {
		do_action( 'backwpup_after_delete_backups', $files, $destination );
	}

	/**
	 * Implement this to return the service name for this destination.
	 */
	abstract public function get_service_name(): string;
}
