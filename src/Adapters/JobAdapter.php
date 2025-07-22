<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Adapters;

/**
 * Class JobAdapter
 *
 * This class provides an adapter for managing BackWPup jobs. It includes methods
 * to disable, enable, schedule, rename, and delete jobs by interacting with the
 * BackWPup_Job class.
 */
class JobAdapter {
	/**
	 * Disables a BackWPup job by its ID.
	 *
	 * This method disables a scheduled job in the BackWPup plugin
	 * by calling the static `disable_job` method of the `BackWPup_Job` class.
	 *
	 * @param int $job_id The ID of the job to disable.
	 *
	 * @return void
	 */
	public function disable_job( int $job_id ): void {
		\BackWPup_Job::disable_job( $job_id );
	}

	/**
	 * Enables a BackWPup job by its ID.
	 *
	 * This method calls the static `enable_job` method of the `BackWPup_Job` class
	 * to enable a job with the specified job ID.
	 *
	 * @param int $job_id The ID of the job to enable.
	 *
	 * @return void
	 */
	public function enable_job( int $job_id ): void {
		\BackWPup_Job::enable_job( $job_id );
	}

	/**
	 * Schedules a BackWPup job by its ID.
	 *
	 * This method delegates the scheduling of a job to the BackWPup_Job class.
	 *
	 * @param int $job_id The ID of the job to be scheduled.
	 *
	 * @return void
	 */
	public function schedule_job( int $job_id ): void {
		\BackWPup_Job::schedule_job( $job_id );
	}

	/**
	 * Renames a BackWPup job with the specified new name.
	 *
	 * @param int    $job_id   The ID of the job to rename.
	 * @param string $new_name The new name to assign to the job.
	 *
	 * @return void
	 */
	public function rename_job( int $job_id, string $new_name ): void {
		\BackWPup_Job::rename_job( $job_id, $new_name );
	}

	/**
	 * Deletes a BackWPup job by its ID.
	 *
	 * This method calls the static `delete_job` method of the `BackWPup_Job` class
	 * to remove a job from the system based on the provided job ID.
	 *
	 * @param int $job_id The ID of the job to be deleted.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function delete_job( int $job_id ): bool {
		return \BackWPup_Job::delete_job( $job_id );
	}

	/**
	 * Get the list of jobs from the backwpup_jobs site option.
	 *
	 * @return array|bool The list of jobs.
	 */
	public function get_jobs() {
		return \BackWPup_Job::get_jobs();
	}

	/**
	 * Get a url to run a job of BackWPup.
	 *
	 * @param string $starttype Start types are 'runnow', 'runnowlink', 'cronrun', 'runext', 'restart', 'restartalt',
	 *                          'test'.
	 * @param int    $jobid     The id of job to start else 0.
	 *
	 * @return array|object [url] is the job url [header] for auth header or object form wp_remote_get()
	 */
	public static function get_jobrun_url( string $starttype, int $jobid = 0 ) {
		return \BackWPup_Job::get_jobrun_url( $starttype, $jobid );
	}

	/**
	 * Sanitizes a filename, replacing whitespace with underscores.
	 *
	 * @param mixed $filename The filename to sanitize.
	 *
	 * @return mixed
	 */
	public function sanitize_file_name( $filename ) {
		return \BackWPup_Job::sanitize_file_name( $filename );
	}
}
