<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Adapters;

class JobTypesAdapter {

	/**
	 * The name of mixed job type.
	 *
	 * @var string
	 */
	public string $both = 'Files & Database';

	/**
	 * The name of the job type for file backups.
	 *
	 * @var string
	 */
	public string $files = 'Files';

	/**
	 * The name of the job type for database backup.
	 *
	 * @var string
	 */
	public string $database = 'Database';

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

	/**
	 * Retrieves the name of job for mixed job type.
	 *
	 * @return string
	 */
	public function get_name_job_both(): string {
		return \BackWPup_JobTypes::$name_job_both;
	}

	/**
	 * Get job type value
	 *
	 * @param string $type
	 *
	 * @return array
	 */
	public function job_type_map( string $type ): array {
		$types = [
			'files'    => $this->get_type_job_file(),
			'database' => $this->get_type_job_database(),
			'mixed'    => $this->get_type_job_both(),
		];

		return $types[ $type ];
	}

	/**
	 * Get the job name
	 *
	 * @param string $name The name of the job.
	 *
	 * @return string
	 */
	public function job_name_map( string $name ): string {
		$names = [
			'files'    => $this->get_name_job_files(),
			'database' => $this->get_name_job_database(),
			'mixed'    => $this->get_name_job_both(),
		];

		return $names[ $name ];
	}
}
