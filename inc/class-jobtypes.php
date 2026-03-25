<?php

abstract class BackWPup_JobTypes {

	/**
	 * The type of job for files only backup.
	 *
	 * @var array
	 */
	public static $type_job_files = [ 'FILE', 'WPPLUGIN' ];

	/**
	 * The type of job for database only backup.
	 *
	 * @var array
	 */
	public static $type_job_database = [ 'DBDUMP' ];

	/**
	 * The type of job for both files and database backup.
	 *
	 * @var array
	 */
	public static $type_job_both = [ 'FILE', 'DBDUMP', 'WPPLUGIN' ];

	/**
	 * The name of the job type for file backups.
	 *
	 * @var string
	 */
	public static $name_job_files = 'Files';

	/**
	 * The name of the job type for database backup.
	 *
	 * @var string
	 */
	public static $name_job_database = 'Database';

	/**
	 *  The name of the job type that includes both files and database backup.
	 *
	 * @var string
	 */
	public static $name_job_both = 'Files & Database';

	/**
	 * The info of job type.
	 *
	 * @var array
	 */
	public $info = [];

	/**
	 * Constructs the job type.
	 */
	abstract public function __construct();

	/**
	 * Returns the default options.
	 *
	 * @return array Default options.
	 */
	abstract public function option_defaults();

	/**
	 * Renders the job type edit tab.
	 *
	 * @param int|array $jobid Job ID or list of job IDs.
	 */
	abstract public function edit_tab( $jobid );

	/**
	 * Saves the job type settings.
	 *
	 * @param int|array $jobid Job ID or list of job IDs.
	 */
	abstract public function edit_form_post_save( $jobid );

	/**
	 * Enqueues scripts for the job type edit tab.
	 *
	 * @return void
	 */
	public function admin_print_scripts() {
	}

	/**
	 * Outputs inline JavaScript for the job type edit tab.
	 *
	 * @return void
	 */
	public function edit_inline_js() {
	}

	/**
	 * Handles job type AJAX requests.
	 *
	 * @return void
	 */
	public function edit_ajax() {
	}

	/**
	 * Enqueues styles for the job type edit tab.
	 *
	 * @return void
	 */
	public function admin_print_styles() {
	}

	/**
	 * Whether the job type creates a file.
	 *
	 * @return bool True if it creates a file, false otherwise.
	 */
	public function creates_file() {
		return false;
	}

	/**
	 * Runs the job type.
	 *
	 * @param BackWPup_Job $job_object Job object.
	 *
	 * @return bool True on success, false on failure.
	 */
	abstract public function job_run( BackWPup_Job $job_object );
}
