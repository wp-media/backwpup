<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Backup;

use BackWPup;
use BackWPup_Job;
use BackWPup_Option;
use WPMedia\BackWPup\Backup\Database\Row\Backup as BackupRow;
use WPMedia\BackWPup\Backup\Database\Queries\Backup as BackupQuery;
use WPMedia\BackWPup\Common\ErrorSignals\ErrorSignalsStore;
use WPMedia\BackWPup\Backup\FailureReasonResolver;

class Database {
	/**
	 * Instance of backups query.
	 *
	 * @var BackupQuery
	 */
	private $backup_query;

	/**
	 * Error signals store instance.
	 *
	 * @var ErrorSignalsStore
	 */
	private $signals_store;

	/**
	 * Failure reason resolver instance.
	 *
	 * @var FailureReasonResolver
	 */
	private $failure_reason_resolver;

	/**
	 * Creates an instance of the class.
	 *
	 * @param BackupQuery           $backup_query Backup Query.
	 * @param ErrorSignalsStore     $signals_store Error signals store.
	 * @param FailureReasonResolver $failure_reason_resolver Failure reason resolver.
	 */
	public function __construct(
		BackupQuery $backup_query,
		ErrorSignalsStore $signals_store,
		FailureReasonResolver $failure_reason_resolver
	) {
		$this->backup_query            = $backup_query;
		$this->signals_store           = $signals_store;
		$this->failure_reason_resolver = $failure_reason_resolver;
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
	 * Get backup database row by ID.
	 *
	 * @param int $backup_id Backup ID.
	 *
	 * @return BackupRow|null
	 */
	public function get_backup_row_by_id( int $backup_id ) {
		if ( $backup_id <= 0 ) {
			return null;
		}

		$item = $this->backup_query->get_item( $backup_id );

		return $item ?: null;
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
	 * Delete failed backup row by ID.
	 *
	 * @param int $backup_id Backup ID.
	 *
	 * @return bool
	 */
	public function delete_failed_backup( int $backup_id ): bool {
		$row = $this->get_backup_row_by_id( $backup_id );

		if ( ! $row || 'failed' !== $row->status ) {
			return false;
		}

		return (bool) $this->backup_query->delete_item( $backup_id );
	}

	/**
	 * Set not completed backups to failed in list of backups.
	 *
	 * @param array        $job Current Job data.
	 * @param BackWPup_Job $backwpup_job
	 *
	 * @return void
	 */
	public function set_not_completed_job_to_failed( $job, BackWPup_Job $backwpup_job ): void {
		$job_id     = isset( $job['jobid'] ) ? (int) $job['jobid'] : 0;
		$backup_ids = BackWPup_Option::get( $job_id, 'backup_ids', [] );

		if ( empty( $backup_ids ) ) {
			return;
		}

		$job_statuses = [];

		$i              = 0;
		$backup_trigger = '';
		foreach ( $backup_ids as $backup_id ) {
			$backup = $this->backup_query->get_item( $backup_id );

			if ( ! $backup ) {

				$message = sprintf(
					/* translators: %s is used for the backup ID */
					__( 'Backup record with ID %s not found in database, skipping status update.', 'backwpup' ),
					$backup_id
				);
				$backwpup_job->log( $message, E_USER_WARNING );

				continue;
			}

			$backup_trigger = $backup->backup_trigger;
			$item_status    = 'completed';

			if ( 'completed' === $backup->status ) {
				$job_statuses[ $i ] = [
					'status'        => $item_status,
					'storage'       => $backup->destination,
					'error_code'    => $backup->error_code,
					'error_message' => $backup->error_message,
				];
				++$i;
				continue;
			}

			$item_status     = 'failed';
			$destination     = is_string( $backup->destination ) ? $backup->destination : '';
			$failure_details = $this->get_failure_details( $job_id, $destination );
			$this->backup_query->set_failed( $backup->id, $failure_details );
			$job_statuses[ $i ] = [
				'status'        => $item_status,
				'storage'       => $backup->destination,
				'error_code'    => $backup->error_code,
				'error_message' => $failure_details['error_message'] ?? $backup->error_message,
			];
			++$i;
		}

		/**
		 * Fires after a job ended.
		 *
		 * @param int $job_id The job id.
		 * @param array $job_statuses Status of the job storages.
		 * @param array $backup_trigger Backup job trigger.
		 */
		do_action( 'backwpup_track_end_job', $job_id, $job_statuses, $backup_trigger );
	}

	/**
	 * Build failure details for a job.
	 *
	 * @param int    $job_id Job ID.
	 * @param string $destination Destination identifier.
	 *
	 * @return array
	 */
	private function get_failure_details( int $job_id, string $destination = '' ): array {
		$details = [];

		if ( $job_id <= 0 ) {
			return $details;
		}

		$details['job_id'] = $job_id;
		$logfile           = $this->get_job_logfile( $job_id );
		$min_timestamp     = $this->get_job_start_timestamp( $job_id );

		if ( '' !== $logfile ) {
			$details['logfile'] = $logfile;
		}

		$signal = $this->get_latest_job_signal( $job_id, $logfile, $min_timestamp, $destination );
		if ( empty( $signal ) && '' !== $destination ) {
			$signal = $this->get_latest_job_signal( $job_id, $logfile, $min_timestamp );
		}
		$reason = $this->failure_reason_resolver->resolve( $job_id, $min_timestamp, $signal, $destination );
		if ( empty( $reason ) && '' !== $destination ) {
			$reason = $this->failure_reason_resolver->resolve( $job_id, $min_timestamp, $signal );
		}

		if ( ! empty( $signal['logfile'] ) ) {
			$details['logfile'] = (string) $signal['logfile'];
		}

		if ( ! empty( $reason ) ) {
			$details = array_merge( $details, $reason );
		} elseif ( ! empty( $signal['message'] ) ) {
			$details['error_message'] = (string) $signal['message'];
		}

		return $details;
	}

	/**
	 * Get the logfile for a job.
	 *
	 * @param int $job_id Job ID.
	 *
	 * @return string
	 */
	private function get_job_logfile( int $job_id ): string {
		$logfile = BackWPup_Option::get( $job_id, 'logfile', '' );

		return is_string( $logfile ) ? $logfile : '';
	}

	/**
	 * Get the most recent job signal for the job.
	 *
	 * @param int    $job_id Job ID.
	 * @param string $logfile Job logfile.
	 * @param int    $min_timestamp Minimum timestamp to accept.
	 * @param string $destination Destination identifier.
	 *
	 * @return array
	 */
	private function get_latest_job_signal( int $job_id, string $logfile, int $min_timestamp = 0, string $destination = '' ): array {
		$signals = $this->signals_store->latest();

		if ( empty( $signals ) ) {
			return [];
		}

		$destination = strtoupper( trim( $destination ) );

		if ( $min_timestamp <= 0 ) {
			$min_timestamp = $this->get_job_start_timestamp( $job_id );
		}
		$warning = [];

		foreach ( $signals as $signal ) {
			if ( (int) ( $signal['job_id'] ?? 0 ) !== $job_id ) {
				continue;
			}

			$signal_time = (int) ( $signal['timestamp'] ?? 0 );
			if ( $min_timestamp > 0 && $signal_time < $min_timestamp ) {
				continue;
			}

			if ( '' !== $logfile && ! empty( $signal['logfile'] ) && $signal['logfile'] !== $logfile ) {
				continue;
			}

			if ( '' !== $destination ) {
				$signal_destination = strtoupper( (string) ( $signal['destination'] ?? '' ) );
				if ( '' === $signal_destination || $signal_destination !== $destination ) {
					continue;
				}
			}

			$level = (string) ( $signal['level'] ?? '' );
			if ( 'error' === $level ) {
				return $signal;
			}

			if ( 'warning' === $level && empty( $warning ) ) {
				$warning = $signal;
			}
		}

		return $warning;
	}

	/**
	 * Get job start timestamp as UTC.
	 *
	 * @param int $job_id Job ID.
	 *
	 * @return int
	 */
	private function get_job_start_timestamp( int $job_id ): int {
		$last_run = BackWPup_Option::get( $job_id, 'lastrun', 0 );
		$last_run = is_numeric( $last_run ) ? (int) $last_run : 0;

		if ( $last_run <= 0 ) {
			return 0;
		}

		$offset = (float) get_option( 'gmt_offset' );
		$utc    = (int) round( $last_run - ( $offset * HOUR_IN_SECONDS ) );

		return max( 0, $utc - 120 );
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
	 * @param string $trigger Backup trigger.
	 *
	 * @return void
	 */
	public function add_backup_row( $job, $filename, $trigger ): void {
		$destinations = BackWPup::get_registered_destinations();
		$backup_ids   = [];
		$job_id       = isset( $job['jobid'] ) ? (int) $job['jobid'] : 0;
		$logfile      = $job_id > 0 ? $this->get_job_logfile( $job_id ) : '';

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

			$backup_ids[ $destination_id ] = $this->backup_query->add( $destination_id, $filename, $trigger, $job_id, $logfile );
		}

		if ( $job_id > 0 ) {
			BackWPup_Option::update( $job_id, 'backup_ids', $backup_ids );
		}
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
			'failed',
		];

		/**
		 * Filters list of statuses to show in the history.
		 * An empty array means no filtering.
		 *
		 * @param array $statuses List of statuses.
		 */
		$statuses               = wpm_apply_filters_typed( 'array', 'backwpup_history_statuses', $statuses );
		$include_failed_backups = empty( $statuses ) || in_array( 'failed', $statuses, true );
		$failed_backups         = $include_failed_backups ? $this->backup_query->query( [ 'status' => 'failed' ] ) : [];

		foreach ( $backups_list as &$item ) {
			$backup_row    = $this->get_backup_row( $item['stored_on'], $item['filename'] );
			$backup_status = ! empty( $backup_row ) ? $backup_row->status : 'completed';

			if (
				! empty( $backup_row )
				&&
				! empty( $statuses )
				&&
				! in_array( $backup_status, $statuses, true )
			) {
				continue;
			}
			if ( ! empty( $backup_row ) ) {
				$item['backup_trigger'] = $backup_row->backup_trigger ?? '';
				$item['status']         = $backup_status;
				$item['error_message']  = 'failed' === $backup_status ? (string) ( $backup_row->error_message ?? '' ) : '';
				$item['logfile']        = 'failed' === $backup_status ? (string) ( $backup_row->logfile ?? '' ) : '';
				$item['backup_id']      = isset( $item['backup_id'] ) ? (int) $item['backup_id'] : 0;
			} else {
				$item['backup_trigger'] = '';
				$item['status']         = $backup_status;
				$item['error_message']  = '';
				$item['logfile']        = '';
				$item['backup_id']      = 0;
			}

			// Keep unique backups with valid status for history.
			$unique_backups[ $item['stored_on'] . $item['filename'] ] = $item;
		}

		if ( $include_failed_backups && ! empty( $failed_backups ) ) {
			foreach ( $failed_backups as $failed_backup ) {
				if ( ! $failed_backup instanceof BackupRow ) {
					continue;
				}
				$failed_item = $this->build_failed_backup_item( $failed_backup );
				if ( empty( $failed_item ) ) {
					continue;
				}
				$key = $failed_item['stored_on'] . $failed_item['filename'];
				if ( isset( $unique_backups[ $key ] ) ) {
					continue;
				}
				$unique_backups[ $key ] = $failed_item;
			}
		}

		if ( empty( $unique_backups ) ) {
			return [];
		}

		$unique_backups = array_values( $unique_backups );
		usort(
			$unique_backups,
			function ( $a, $b ) {
				return ( $b['time'] ?? 0 ) <=> ( $a['time'] ?? 0 );
			}
		);

		return $unique_backups;
	}

	/**
	 * Build a history item from a failed backup row.
	 *
	 * @param BackupRow $backup_row Backup row.
	 *
	 * @return array
	 */
	private function build_failed_backup_item( BackupRow $backup_row ): array {
		$destination = is_string( $backup_row->destination ) ? $backup_row->destination : '';
		$filename    = is_string( $backup_row->filename ) ? $backup_row->filename : '';
		if ( '' === $destination || '' === $filename ) {
			return [];
		}

		$job_id = isset( $backup_row->job_id ) ? (int) $backup_row->job_id : 0;
		$job    = $job_id > 0 ? BackWPup_Option::get_job( $job_id ) : false;
		$job    = is_array( $job ) ? $job : [];
		$time   = $this->resolve_failed_backup_time( $backup_row, $job );
		$data   = ! empty( $job['type'] ) ? (array) $job['type'] : [ 'Unknown' ];

		return [
			'folder'         => '',
			'file'           => $filename,
			'filename'       => $filename,
			'downloadurl'    => '',
			'restoreurl'     => '',
			'filesize'       => 0,
			'time'           => $time,
			'id'             => $job_id,
			'name'           => $job['name'] ?? '',
			'type'           => $job['activetype'] ?? '',
			'data'           => $data,
			'logfile'        => $backup_row->logfile ?? '',
			'last_run'       => $job['lastrun'] ?? null,
			'stored_on'      => $destination,
			'backup_trigger' => $backup_row->backup_trigger ?? '',
			'status'         => 'failed',
			'error_message'  => (string) ( $backup_row->error_message ?? '' ),
			'backup_id'      => (int) $backup_row->id,
		];
	}

	/**
	 * Resolve timestamp for failed backup items.
	 *
	 * @param BackupRow $backup_row Backup row.
	 * @param array     $job Job data.
	 *
	 * @return int
	 */
	private function resolve_failed_backup_time( BackupRow $backup_row, array $job ): int {
		$time = 0;

		if ( ! empty( $backup_row->submitted_at ) ) {
			$time = strtotime( (string) $backup_row->submitted_at );
		}

		if ( $time <= 0 && ! empty( $backup_row->modified ) && '0000-00-00 00:00:00' !== $backup_row->modified ) {
			$time = strtotime( (string) $backup_row->modified );
		}

		if ( $time <= 0 && ! empty( $job['lastrun'] ) && is_numeric( $job['lastrun'] ) ) {
			$time = (int) $job['lastrun'];
		}

		if ( $time <= 0 ) {
			$time = time();
		}

		return $time;
	}
}
