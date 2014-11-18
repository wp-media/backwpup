<?php
/**
 * Class for WP-CLI commands
 */
class BackWPup_WP_CLI extends WP_CLI_Command {

	/**
	 * Start a BackWPup job
	 *
	 * @param $args
	 * @param $assoc_args
	 * @synopsis --jobid=<ID>
	 */
	public function start( $args, $assoc_args ) {

		if ( file_exists( BackWPup::get_plugin_data( 'running_file' ) ) )
			WP_CLI::error( __( 'A job is already running.', 'backwpup' ) );

		if ( empty( $assoc_args['jobid'] ) )
			WP_CLI::error( __( 'No job ID specified!', 'backwpup' ) );

		$jobids = BackWPup_Option::get_job_ids();
		if ( ! in_array( $assoc_args['jobid'], $jobids ) )
			WP_CLI::error( __( 'Job ID does not exist!', 'backwpup' ) );

		BackWPup_Job::start_cli( $assoc_args['jobid'] );

	}

	/**
	 *  Abort a working BackWPup Job
	 *
	 */
	public function abort( $args, $assoc_args ) {

		if ( file_exists( BackWPup::get_plugin_data( 'running_file' ) ) )
			WP_CLI::error( __( 'Nothing to abort!', 'backwpup' ) );

		//abort
		BackWPup_Job::user_abort();
		WP_CLI::success( __( 'Job will be terminated.', 'backwpup' ) ) ;
	}

	/**
	 * Display a List of Jobs
	 *
	 */
	public function jobs( $args, $assoc_args ) {

		$jobids = BackWPup_Option::get_job_ids();

		WP_CLI::line( __('List of jobs', 'backwpup' ) );
		WP_CLI::line( '----------------------------------------------------------------------' );
		foreach ($jobids as $jobid ) {
			WP_CLI::line( sprintf( __('ID: %1$d Name: %2$s', 'backwpup' ),$jobid, BackWPup_Option::get( $jobid, 'name' ) ) );
		}

	}

	/**
	 * See Status of a working job
	 *
	 * @param $args
	 * @param $assoc_args
	 */
	public function working( $args, $assoc_args ) {

		$job_object = BackWPup_Job::get_working_data();
		if ( is_object( $job_object ) )
			WP_CLI::error( __( 'No job running', 'backwpup' ) );
		WP_CLI::line( __('Running job', 'backwpup' ) );
		WP_CLI::line( '----------------------------------------------------------------------' );
		WP_CLI::line( sprintf( __( 'ID: %1$d Name: %2$s', 'backwpup' ), $job_object->job[ 'jobid' ], $job_object->job[ 'name' ] ) );
		WP_CLI::line( sprintf( __( 'Warnings: %1$d Errors: %2$d', 'backwpup' ), $job_object->warnings , $job_object->errors ) );
		WP_CLI::line( sprintf( __( 'Steps in percent: %1$d percent of step: %2$d', 'backwpup' ), $job_object->step_percent, $job_object->substep_percent ) );
		WP_CLI::line( sprintf( __( 'On step: %s', 'backwpup' ), $job_object->steps_data[ $job_object->step_working ][ 'NAME' ] ) );
		WP_CLI::line( sprintf( __( 'Last message: %s', 'backwpup' ), str_replace( '&hellip;', '...', strip_tags( $job_object->lastmsg ) ) ) );

	}

}
