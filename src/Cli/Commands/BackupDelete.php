<?php

namespace WPMedia\BackWPup\Cli\Commands;

use BackWPup_Destination_Downloader;
use BackWPup_Destination_Downloader_Factory;
use BackWPup_Factory_Exception;
use WPMedia\BackWPup\Adapters\BackWPupAdapter;
use WPMedia\BackWPup\Adapters\JobAdapter;

class BackupDelete implements Command {

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
	 * Constructor method.
	 *
	 * @param JobAdapter      $job_adapter The job adapter instance.
	 * @param BackWPupAdapter $backwpup_adapter The BackWPup adapter instance.
	 *
	 * @return void
	 */
	public function __construct( JobAdapter $job_adapter, BackWPupAdapter $backwpup_adapter ) {
		$this->job_adapter      = $job_adapter;
		$this->backwpup_adapter = $backwpup_adapter;
	}

	/**
	 * Delete BackWPup backup archives on storages.
	 *
	 * ## OPTIONS
	 *
	 * <file>
	 * : Backup archive file name that should be deleted.
	 *
	 * [--storage=<storage>]
	 * : Storage where the file will be deleted from. (Default: all that have this file)
	 *
	 * [--yes]
	 * : Don't ask for confirmation.
	 *
	 * ## EXAMPLES
	 *
	 *     # Delete backup archive with the given name from all storages
	 *     $ wp backwpup backup-delete 2025-10-23_05-56-19_VPIUOYIO04_FILE-WPPLUGIN.tar
	 *     Confirm: Delete Backup file 2025-10-23_05-56-19_VPIUOYIO04_FILE-WPPLUGIN.tar on S3 ?
	 *     Success: Backup file 2025-10-23_05-56-19_VPIUOYIO04_FILE-WPPLUGIN.tar deleted successfully on S3.
	 *
	 *     # Delete backup archive with the given name from all storages, without asking for confirmation.
	 *     $ wp backwpup backup-delete 2025-10-23_05-56-19_VPIUOYIO06_FILE-WPPLUGIN.tar --yes
	 *     Success: Backup file 2025-10-23_05-56-19_VPIUOYIO06_FILE-WPPLUGIN.tar deleted successfully on hidrive.
	 *
	 *     # Delete backup archive only from HiDrive
	 *     $ wp backwpup backup-delete 2025-10-23_05-56-19_VPIUOYIO03_FILE-WPPLUGIN.tar --storage=hidrive
	 *     Confirm: Delete Backup file 2025-10-23_05-56-19_VPIUOYIO03_FILE-WPPLUGIN.tar on hidrive ?
	 *     Success: Backup file 2025-10-23_05-56-19_VPIUOYIO03_FILE-WPPLUGIN.tar deleted successfully on hidrive.
	 *
	 * @alias backups-delete
	 *
	 * @param array $args Positional arguments passed to the method.
	 * @param array $assoc_args Associative arguments passed to the method.
	 *
	 * @return void
	 */
	public function __invoke( array $args, array $assoc_args ) {

		$deleted                 = false;
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
				$backups = $dest_object->file_get_list( $job['jobid'] . '_' . $destination );
				foreach ( $backups as $backup ) {
					if ( strtolower( trim( $args[0] ) ) !== strtolower( $backup['filename'] ) ) {
						continue;
					}
					if ( ! isset( $assoc_args['yes'] ) ) {
						/* translators: %s: Backup file name. %s: Storage name. */
						\WP_CLI::confirm( sprintf( __( 'Delete Backup file %1$s on %2$s ?', 'backwpup' ), trim( $args[0] ), ucfirst( strtolower( $destination ) ) ) );
					}
					$dest_object->file_delete( $job['jobid'] . '_' . $destination, $backup['file'] );
					$messages = \BackWPup_Admin::get_messages();
					if ( $messages ) {
						if ( ! empty( $messages['error'] ) ) {
							foreach ( $messages['error'] as $message ) {
								\WP_CLI::error( $message );
							}
							$messages['error'] = [];
							update_site_option( 'backwpup_messages', $messages );
						}
					}
					$deleted = true;
					/* translators: %1$s: Backup file name, %2$s: Storage name. */
					\WP_CLI::success( sprintf( __( 'Backup file %1$s deleted successfully on %2$s.', 'backwpup' ), $backup['file'], ucfirst( strtolower( $destination ) ) ) );
				}
			}
		}

		if ( ! $deleted ) {
			/* translators: %s: Backup file name. */
			\WP_CLI::error( sprintf( __( 'No Backup file %s found for deletion.', 'backwpup' ), trim( $args[0] ) ) );
		}
	}

	/**
	 * Retrieves the command name.
	 *
	 * @inheritDoc
	 */
	public function get_name(): string {
		return 'backup-delete';
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
