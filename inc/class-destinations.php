<?php
/**
 * Base class for adding BackWPup destinations.
 *
 * @package    BackWPup
 * @subpackage BackWPup_Destinations
 * @since      3.0.0
 * @access private
 */
abstract class BackWPup_Destinations {

	/**
	 * @return array
	 */
	abstract public function option_defaults();

	/**
	 * @param $jobid
	 */
	abstract public function edit_tab( $jobid );

	/**
	 * @param $jobid
	 */
	public function edit_auth( $jobid ) {

	}

	/**
	 * @param $jobid
	 */
	abstract public function edit_form_post_save( $jobid );

	/**
	 * use wp_enqueue_script() here to load js for tab
	 */
	public function admin_print_scripts() {

	}

	/**
	 *
	 */
	public function edit_inline_js() {

	}

	/**
	 *
	 */
	public function edit_ajax() {

	}

	/**
	 *
	 */
	public function wizard_admin_print_styles() {

	}

	/**
	 *
	 */
	public function wizard_admin_print_scripts() {

	}

	/**
	 *
	 */
	public function wizard_inline_js() {

	}

	/**
	 * @param $job_settings
	 */
	public function wizard_page( $job_settings ) {

		echo '<br /><pre>';
		print_r( $job_settings );
		echo '</pre>';
	}

	/**
	 * @param $job_settings
	 */
	public function wizard_save( $job_settings ) {

		return $job_settings;
	}

	/**
	 *
	 */
	public function admin_print_styles() {

	}

	/**
	 * @param $jobdest
	 * @param $backupfile
	 */
	public function file_delete( $jobdest, $backupfile ) {

	}

	/**
	 * @param $jobid
	 * @param $get_file
	 */
	public function file_download( $jobid, $get_file ) {

	}

	/**
	 * @param $jobdest
	 * @return bool
	 */
	public function file_get_list( $jobdest ) {

		return FALSE;
	}

	/**
	 * @param $job_object BackWPup_Job Object
	 */
	abstract public function job_run_archive( &$job_object );

	/**
	 * @param $job_object BackWPup_Job Object
	 */
	public function job_run_sync( &$job_object ) {

	}

	/**
	 * @param $job_object BackWPup_Job Object
	 * @return bool
	 */
	abstract public function can_run( $job_object );
}
