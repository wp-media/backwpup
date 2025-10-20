<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Backup;

use BackWPup;
use BackWPup_Option;
use WPMedia\BackWPup\Backup\Database\Row\Backup as BackupRow;
use WPMedia\BackWPup\Backup\Database\Queries\Backup as BackupQuery;

class Database {
	/**
	 * Instance of backups query.
	 *
	 * @var BackupQuery
	 */
	private $backup_query;

	/**
	 * Creates an instance of the class.
	 *
	 * @param BackupQuery $backup_query Backup Query.
	 */
	public function __construct( BackupQuery $backup_query ) {
		$this->backup_query = $backup_query;
	}

	/**
	 * Get backup database row based on destination and filename.
	 *
	 * @param string $destination_id Destination ID.
	 * @param string $filename Backup filename.
	 *
	 * @return BackupRow|null
	 */
	public function get_backup_row( $destination_id, $filename ) {
		$items = $this->backup_query->query(
			[
				'destination' => $destination_id,
				'filename'    => $filename,
				'number'      => 1,
			]
		);
		return ! empty( $items ) ? $items[0] : null;
	}

	/**
	 * Delete backup row.
	 *
	 * @param string $destination_id Destination ID.
	 * @param string $filename Backup filename.
	 *
	 * @return bool
	 */
	public function delete_backup( $destination_id, $filename ) {
		$row = $this->get_backup_row( $destination_id, $filename );

		if ( ! $row ) {
			return false;
		}
		return $this->backup_query->delete_item( $row->id );
	}

	/**
	 * Set not completed backups to failed in list of backups.
	 *
	 * @param array $job Current Job.
	 *
	 * @return void
	 */
	public function set_not_completed_job_to_failed( $job ): void {
		$backup_ids = BackWPup_Option::get( $job['jobid'], 'backup_ids', [] );

		if ( empty( $backup_ids ) ) {
			return;
		}

		$job_statuses = [];

		$status = 'completed';
		$i      = 0;
		foreach ( $backup_ids as $backup_id ) {
			$backup = $this->backup_query->get_item( $backup_id );

			if (
				! $backup
				||
				'completed' === $backup->status
			) {
				$job_statuses[ $i ] = [
					'status'        => $status,
					'storage'       => $backup->destination,
					'error_code'    => $backup->error_code,
					'error_message' => $backup->error_message,
				];
				++$i;
				continue;
			}

			$status = 'failed';
			$this->backup_query->set_status( $backup->id, 'failed' );
			$job_statuses[ $i ] = [
				'status'        => $status,
				'storage'       => $backup->destination,
				'error_code'    => $backup->error_code,
				'error_message' => $backup->error_message,
			];
			++$i;
		}

		/**
		 * Fires after a job ended.
		 *
		 * @param int $job_id The job id.
		 * @param array $job_statuses Status of the job storages.
		 */
		do_action( 'backwpup_track_end_job', $job['jobid'], $job_statuses );
	}

	/**
	 * Set backup row to completed status.
	 *
	 * @param array  $job Current Job.
	 * @param string $destination Backup destination.
	 *
	 * @return void
	 */
	public function set_backup_completed( $job, $destination ): void {
		$backup_ids = BackWPup_Option::get( $job['jobid'], 'backup_ids', [] );

		if (
			empty( $backup_ids )
			||
			empty( $backup_ids[ $destination ] )
		) {
			return;
		}

		$this->backup_query->set_status( $backup_ids[ $destination ], 'completed' );
	}

	/**
	 * Insert new backup row
	 *
	 * @param array  $job Current Job.
	 * @param string $filename Backup filename.
	 *
	 * @return void
	 */
	public function add_backup_row( $job, $filename ): void {
		$destinations = BackWPup::get_registered_destinations();
		$backup_ids   = [];

		foreach ( $destinations as $destination_id => $destination ) {
			if (
				empty( $job['destinations'] )
				||
				! in_array( $destination_id, $job['destinations'], true )
				||
				empty( $destination['class'] )
			) {
				continue;
			}

			$backup_ids[ $destination_id ] = $this->backup_query->add( $destination_id, $filename );
		}

		BackWPup_Option::update( $job['jobid'], 'backup_ids', $backup_ids );
	}

	/**
	 * Delete backup rows
	 *
	 * @param array  $backup_files Array of backup files.
	 * @param string $destination Backup destination.
	 *
	 * @return void
	 */
	public function delete_backup_rows( $backup_files, $destination ): void {
		if (
			empty( $backup_files )
			||
			empty( $destination )
		) {
			return;
		}

		foreach ( $backup_files as $backup_file ) {
			$filename = basename( $backup_file );
			$this->delete_backup( $destination, $filename );
		}
	}

	/**
	 * Get list of backups by status.
	 *
	 * @param string $status Status of backups to filter by.
	 *
	 * @return array
	 */
	public function backups_list_by_status( string $status = '' ) {
		$arg = [];

		if ( ! empty( $status ) ) {
			$arg = [
				'status' => $status,
			];
		}
		$items = $this->backup_query->query( $arg );

		return ! empty( $items ) ? $items : [];
	}

	/**
	 * Filters items in list of backups history to show only items with matching status.
	 *
	 * @param array $backups_list Backups list.
	 *
	 * @return array
	 */
	public function backups_list( $backups_list ) {
		$unique_backups = [];
		$statuses       = [
			'completed',
		];

		/**
		 * Filters list of statuses to show in the history.
		 * An empty array means no filtering.
		 *
		 * @param array $statuses List of statuses.
		 */
		$statuses = wpm_apply_filters_typed( 'array', 'backwpup_history_statuses', $statuses );

		foreach ( $backups_list as &$item ) {
			$backup_row = $this->get_backup_row( $item['stored_on'], $item['filename'] );

			if (
				! empty( $backup_row )
				&&
				! empty( $statuses )
				&&
				! in_array( $backup_row->status, $statuses, true )
			) {
				continue;
			}

			// Keep unique backups with valid status for history.
			$unique_backups[ $item['stored_on'] . $item['filename'] ] = $item;
		}

		if ( empty( $unique_backups ) ) {
			return [];
		}

		return array_values( $unique_backups );
	}
}
