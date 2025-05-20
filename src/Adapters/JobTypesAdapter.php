<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Adapters;

class JobTypesAdapter {
	/**
	 * Get the type array for file jobs.
	 *
	 * @return array
	 */
	public function get_type_job_file(): array {
		return \BackWPup_JobTypes::$type_job_files;
	}

	/**
	 * Get the type array for database jobs.
	 *
	 * @return array
	 */
	public function get_type_job_database(): array {
		return \BackWPup_JobTypes::$type_job_database;
	}

	/**
	 * Retrieves an array of job types that are applicable to both conditions.
	 *
	 * @return array An array of job types that satisfy both conditions.
	 */
	public function get_type_job_both(): array {
		return \BackWPup_JobTypes::$type_job_both;
	}

	/**
	 * Get the name for file jobs.
	 *
	 * @return string
	 */
	public function get_name_job_files(): string {
		return \BackWPup_JobTypes::$name_job_files;
	}

	/**
	 * Get the name for database jobs.
	 *
	 * @return string
	 */
	public function get_name_job_database(): string {
		return \BackWPup_JobTypes::$name_job_database;
	}
}
