<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Backup;

use WPMedia\BackWPup\EventManagement\SubscriberInterface;

class Subscriber implements SubscriberInterface {
	/**
	 * Database class instance.
	 *
	 * @var Database
	 */
	private $database;

	/**
	 * Constructor.
	 *
	 * @param Database $database Database instance.
	 */
	public function __construct( Database $database ) {
		$this->database = $database;
	}

	/**
	 * WP Hooks callbacks.
	 *
	 * @return array
	 */
	public static function get_subscribed_events(): array {
		return [
			'backwpup_create_job'             => [ 'add_backup_row', 10, 2 ],
			'backwpup_end_job'                => 'set_not_completed_backups_to_failed',
			'backwpup_job_success'            => [ 'set_backup_completed', 10, 2 ],
			'backwpup_after_delete_backups'   => [ 'delete_backup_rows', 10, 2 ],
			'backwpup_backups_list'           => 'backups_list',
			'backwpup_backups_list_by_status' => 'backups_list_status',
		];
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
		$this->database->add_backup_row( $job, $filename );
	}

	/**
	 * Set not completed backups to failed at the end of the process.
	 *
	 * @param array $job Current Job.
	 *
	 * @return void
	 */
	public function set_not_completed_backups_to_failed( $job ): void {
		$this->database->set_not_completed_job_to_failed( $job );
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
		$this->database->set_backup_completed( $job, $destination );
	}

	/**
	 * Delete backup rows
	 *
	 * @param array  $backup_files Array of backup files.
	 * @param string $destination Backup destination.
	 *
	 * @return void
	 */
	public function delete_backup_rows( $backup_files, $destination ) {
		$this->database->delete_backup_rows( $backup_files, $destination );
	}

	/**
	 * Filters items in list of backups history to show only items with matching status.
	 *
	 * @param array $backups_list Backups list.
	 *
	 * @return array
	 */
	public function backups_list( $backups_list ) {
		return $this->database->backups_list( $backups_list );
	}

	/**
	 * Get backup rows by status.
	 *
	 * @param string $status The status to filter by.
	 */
	public function backups_list_status( string $status = '' ) {
		return $this->database->backups_list_by_status( $status );
	}
}
