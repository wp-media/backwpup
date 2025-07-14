<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Adapters;

/**
 * Class OptionAdapter
 *
 * Adapter for BackWPup_Option static methods.
 */
class OptionAdapter {
	/**
	 * Update an option for a job.
	 *
	 * @param int    $job_id The job ID.
	 * @param string $key    The option key.
	 * @param mixed  $value  The value to set.
	 *
	 * @return void
	 */
	public function update( int $job_id, string $key, $value ): void {
		\BackWPup_Option::update( $job_id, $key, $value );
	}

	/**
	 * Get an option for a job.
	 *
	 * @param int    $job_id The job ID.
	 * @param string $key    The option key.
	 *
	 * @return mixed
	 */
	public function get( int $job_id, string $key ) {
		return \BackWPup_Option::get( $job_id, $key );
	}

	/**
	 * Get default job options.
	 *
	 * @param string $key Option key (optional).
	 *
	 * @return mixed
	 */
	public function defaults_job( string $key = '' ) {
		return \BackWPup_Option::defaults_job( $key );
	}

	/**
	 * Get the next job ID.
	 *
	 * @return mixed
	 */
	public function next_job_id() {
		return \BackWPup_Option::next_job_id();
	}

	/**
	 * Get job IDs optionally filtered by a specific option key and value.
	 *
	 * @param string|null $key   Option key to filter by, or null to get all job IDs.
	 * @param mixed       $value Expected value of the option.
	 *
	 * @return array List of job IDs.
	 */
	public function get_job_ids( $key = null, $value = false ) {
		return \BackWPup_Option::get_job_ids( $key, $value );
	}

	/**
	 * Get BackWPup Job Options.
	 *
	 * @param int  $id        The job ID.
	 * @param bool $use_cache Whether to use the cache.
	 *
	 * @return array|false Array of all job options if found, false otherwise.
	 */
	public function get_job( $id, $use_cache = true ) {
		return \BackWPup_Option::get_job( $id, $use_cache );
	}

	/**
	 * Delete a BackWPup Option.
	 *
	 * @param int    $jobid  The job ID.
	 * @param string $option The option key to delete.
	 *
	 * @return bool True if deleted successfully, false otherwise.
	 */
	public function delete( int $jobid, string $option ) {
		return \BackWPup_Option::delete( $jobid, $option );
	}

	/**
	 * Normalizes the archive name.
	 *
	 * The archive name should include the hash to identify this site, and the job id to identify this job.
	 *
	 * This allows backup files belonging to this job to be tracked.
	 *
	 * @param string $archive_name The archive name.
	 * @param int    $jobid        The job id.
	 * @param bool   $substitute_hash Substitute hash.
	 *
	 * @return string The normalized archive name
	 */
	public function normalize_archive_name( string $archive_name, int $jobid, $substitute_hash = true ) {
		return \BackWPup_Option::normalize_archive_name( $archive_name, $jobid, $substitute_hash );
	}
}
