<?php

namespace WPMedia\BackWPup\Cli\Commands;

use BackWPup_Destination_Downloader;
use BackWPup_Destination_Downloader_Factory;
use BackWPup_Factory_Exception;
use WPMedia\BackWPup\Adapters\BackWPupAdapter;
use WPMedia\BackWPup\Adapters\JobAdapter;

class BackupDownload implements Command {

	/**
	 * The job adapter instance.
	 *
	 * @var JobAdapter
	 */
	private JobAdapter $job_adapter;

	/**
	 * The BackWPup adapter instance.
	 *
	 * @var BackWPupAdapter
	 */
	private BackWPupAdapter $backwpup_adapter;

	/**
	 * The BackWPup destination downloader factory instance.
	 *
	 * @var \BackWPup_Destination_Downloader_Factory
	 */
	private BackWPup_Destination_Downloader_Factory $dl_factory;

	/**
	 * Constructor method.
	 *
	 * @param JobAdapter                               $job_adapter The job adapter instance.
	 * @param BackWPupAdapter                          $backwpup_adapter The BackWPup adapter instance.
	 * @param \BackWPup_Destination_Downloader_Factory $dl_factory
	 *
	 * @return void
	 */
	public function __construct( JobAdapter $job_adapter, BackWPupAdapter $backwpup_adapter, \BackWPup_Destination_Downloader_Factory $dl_factory ) {
		$this->job_adapter      = $job_adapter;
		$this->backwpup_adapter = $backwpup_adapter;
		$this->dl_factory       = $dl_factory;
	}

	/**
	 * Download a BackWPup backup archive from a storage. Shows progress bar.
	 *
	 * ## OPTIONS
	 *
	 * <file>
	 * : Backup archive file name that should be downloaded.
	 *
	 * [<to_file>]
	 * : Folder or filename to download to. Default: current working directory with the same name as the backup file.
	 *
	 * [--storage=<storage>]
	 * : Storage where the file will be downloaded from. (Default: use first found storage.)
	 *
	 * [--yes]
	 * : Overwrite a local existing file. (Default: prompt for overwriting)
	 *
	 * ## EXAMPLES
	 *
	 *     # Download a backup archive from first found storage.
	 *     $ wp backwpup backup-download 2025-10-23_05-56-19_VPIUOYIO03_FILE-WPPLUGIN.tar
	 *     Start download from Folder:wp-content/uploads/backwpup/d14761/backups/2025-10-23_05-56-19_VPIUOYIO03_FILE-WPPLUGIN.tar to ./2025-10-23_05-56-19_VPIUOYIO03_FILE-WPPLUGIN.tar.
	 *     Download 2025-10-23_05-56-19_VPIUOYIO03_FILE-WPPLUGIN.tar:  80% [====================================================--------] 0:01 / 0:01
	 *     Success: Backup file downloaded successfully.
	 *
	 *     # Download a backup archive from HiDrive
	 *     $ wp backwpup backup-download 2025-10-23_05-56-19_VPIUOYIO04_FILE-WPPLUGIN.tar --storage=hidrive
	 *     Error: Backup file not found in storage hidrive.
	 *
	 *     # Download a backup archive from first found storage to file test.tar.gz
	 *     $ wp backwpup backup-download 2025-10-23_05-56-19_VPIUOYIO03_FILE-WPPLUGIN.tar test.tar.gz
	 *     Confirm: File test.tar.gz already exists. Overwrite it? [y/n]
	 *     Start download from Folder:wp-content/uploads/backwpup/d14761/backups/2025-10-23_05-56-19_VPIUOYIO03_FILE-WPPLUGIN.tar to ./test.tar.gz.
	 *     Download 2025-10-23_05-56-19_VPIUOYIO03_FILE-WPPLUGIN.tar:  80% [====================================================--------] 0:01 / 0:01
	 *     Success: Backup file downloaded successfully.
	 *
	 * @alias backups-download
	 *
	 * @param array $args Positional arguments passed to the method.
	 * @param array $assoc_args Associative arguments passed to the method.
	 *
	 * @return void
	 */
	public function __invoke( array $args, array $assoc_args ) {

		$found                   = [];
		$jobs                    = $this->job_adapter->get_jobs();
		$registered_destinations = $this->backwpup_adapter->get_registered_destinations();
		foreach ( $jobs as $job ) {
			if ( ! empty( $job['tempjob'] ) ) {
				continue;
			}

			if ( ! $job['destinations'] ) {
				$job['destinations'] = [];
			}
			foreach ( $job['destinations'] as $destination ) {
				if ( isset( $assoc_args['storage'] ) && strtoupper( $assoc_args['storage'] ) !== $destination ) {
					continue;
				}
				if ( empty( $registered_destinations[ $destination ]['class'] ) ) {
					continue;
				}
				$dest_object = $this->backwpup_adapter->get_destination( $destination );
				if ( is_array( $dest_object ) ) {
					continue;
				}
				$class_name        = get_class( $dest_object );
				$class_name_parts  = explode( '_', $class_name );
				$class_destination = array_pop( $class_name_parts );
				$backups           = $dest_object->file_get_list( $job['jobid'] . '_' . $destination );
				foreach ( $backups as $backup ) {
					if ( strtolower( trim( $args[0] ) ) !== strtolower( $backup['filename'] ) ) {
						continue;
					}
					$found = [
						'storage'      => $destination,
						'job_id'       => $job['jobid'],
						'file'         => $backup['file'],
						'name'         => $backup['filename'],
						'class'        => $class_destination,
						'download_url' => $backup['downloadurl'],
					];
					break 3;
				}
			}
		}

		if ( empty( $found ) ) {
			if ( isset( $assoc_args['storage'] ) ) {
				/* translators: %s: Storage name. */
				\WP_CLI::error( sprintf( __( 'Backup file not found in storage %s.', 'backwpup' ), $assoc_args['storage'] ) );
				return;
			}
			\WP_CLI::error( 'Backup file not found.' );
			return;
		}

		if ( isset( $args[1] ) ) {
			$to_file = $args[1];
		} else {
			$to_file = $found['name'];
		}
		if ( is_dir( $to_file ) ) {
			$to_file = trailingslashit( $to_file ) . $found['name'];
		} else {
			if ( '/' === substr( $to_file, -1 ) || '\\' === substr( $to_file, -1 ) ) {
				$to_file .= $found['name'];
			}
			$to_dir = dirname( $to_file );
			if ( ! realpath( $to_dir ) ) {
				$result = wp_mkdir_p( $to_dir );
				if ( ! $result ) {
					// translators: %s: Directory path.
					\WP_CLI::error( sprintf( __( 'Could not create directory %s.', 'backwpup' ), $to_dir ) );
					return;
				}
			}
		}

		if ( ! isset( $assoc_args['yes'] ) && file_exists( $to_file ) ) {
			/* translators: %s: Backup file name. */
			\WP_CLI::confirm( sprintf( __( 'File %s already exists. Overwrite it?', 'backwpup' ), $to_file ) );
		}

		try {
			/* translators: %s: storage name */
			\WP_CLI::line( sprintf( __( 'Start download from %1$s to %2$s.', 'backwpup' ), $found['class'] . ':' . $found['file'], $to_file ) );

			$destination_downloader = $this->dl_factory->create(
				$found['class'],
				$found['job_id'],
				$found['file'],
				$to_file
			);
			$destination_downloader->download_by_chunks_wp_cli();
		}  catch ( BackWPup_Factory_Exception $e ) {
			/* translators: %s: storage name */
			\WP_CLI::error( sprintf( __( 'The download for %s is currently not supported by BackWPup. Please download the file directly there!', 'backwpup' ), $found['class'] ) );
			return;
		}

		\WP_CLI::success( __( 'Backup file downloaded successfully.', 'backwpup' ) );
	}

	/**
	 * Retrieves the command name.
	 *
	 * @inheritDoc
	 */
	public function get_name(): string {
		return 'backup-download';
	}

	/**
	 * Retrieves the arguments for the command.
	 *
	 * @inheritDoc
	 */
	public function get_args(): array {
		return [];
	}
}
