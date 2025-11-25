<?php

namespace WPMedia\BackWPup\Cli\Commands;

use WP_CLI;
use WPMedia\BackWPup\Adapters\BackWPupAdapter;
use WPMedia\BackWPup\Adapters\JobAdapter;


class Status implements Command {

	/**
	 * The BackWPup adapter instance.
	 *
	 * @var BackWPupAdapter
	 */
	private BackWPupAdapter $backwpup_adapter;

	/**
	 * The job adapter instance.
	 *
	 * @var JobAdapter
	 */
	private JobAdapter $job_adapter;

	/**
	 * Constructor method.
	 *
	 * @param BackWPupAdapter $backwpup_adapter The BackWPup adapter instance.
	 * @param JobAdapter      $job_adapter The job adapter instance.
	 *
	 * @return void
	 */
	public function __construct( BackWPupAdapter $backwpup_adapter, JobAdapter $job_adapter ) {
		$this->backwpup_adapter = $backwpup_adapter;
		$this->job_adapter      = $job_adapter;
	}

	/**
	 * Shows the status about a running BackWPup job. With a progress bar.
	 *
	 * ## EXAMPLES
	 *
	 *     # Display the status of a running job.
	 *     $ wp backwpup status
	 *     Success: No job running.
	 *
	 * @alias working
	 *
	 * @param array $args Positional arguments passed to the method.
	 * @param array $assoc_args Associative arguments passed to the method.
	 *
	 * @return void
	 */
	public function __invoke( array $args, array $assoc_args ) {
		$job_object = $this->job_adapter->get_working_data();

		if ( ! is_object( $job_object ) ) {
			\WP_CLI::success( __( 'No job running.', 'backwpup' ) );
			return;
		}

		$log_file = $job_object->logfile;
		\WP_CLI::line( __( 'Current running job information:', 'backwpup' ) );
		// translators: %d: Job ID.
		\WP_CLI::line( sprintf( __( 'Job ID: %d', 'backwpup' ), $job_object->job['jobid'] ) );
		// translators: %s: Job name.
		\WP_CLI::line( sprintf( __( 'Job name: %s', 'backwpup' ), $job_object->job['name'] ) );
		// translators: %s: Job logfile.
		\WP_CLI::line( sprintf( __( 'Logfile: %s', 'backwpup' ), $log_file ) );
		\WP_CLI::line( ' ' );
		\WP_CLI::line( __( 'Progress:', 'backwpup' ) );

		$message       = $this->get_colrefull_warning_errors( $job_object->warnings, $job_object->errors );
		$count         = ( count( $job_object->steps_todo ) + 1 ) * 100;
		$progress      = \WP_CLI\Utils\make_progress_bar( $message, $count );
		$current_count = 0;
		while ( true ) {
			// as long file is there, the job is still running.
			$file_exists = file_exists( $this->backwpup_adapter->get_plugin_data( 'running_file' ) );
			if ( ! $file_exists ) {
				break;
			}
			$job_object = $this->job_adapter->get_working_data();
			if ( ! is_object( $job_object ) ) {
				continue;
			}
			$tick          = count( $job_object->steps_done ) * 100 + $job_object->substep_percent - $current_count;
			$current_count = count( $job_object->steps_done ) * 100 + $job_object->substep_percent;
			$message       = html_entity_decode( wp_strip_all_tags( $job_object->lastmsg ) );
			$progress->tick( $tick, $message );
			usleep( 250 );
		}
		$log_header = $this->job_adapter->read_log_header( $log_file );
		$message    = $this->get_colrefull_warning_errors( $log_header['warnings'], $log_header['errors'] );
		if ( $message ) {
			$progress->tick( -1, $message );
		}
		$progress->finish();
		if ( $log_header['errors'] ) {
			\WP_CLI::error( __( 'Job finished with errors!', 'backwpup' ) );
			return;
		}
		if ( $log_header['warnings'] ) {
			\WP_CLI::warning( __( 'Job finished with warnings.', 'backwpup' ) );
			return;
		}
		\WP_CLI::success( __( 'Job finished.', 'backwpup' ) );
	}

	/**
	 * Generates a colorized message string for warnings and errors.
	 *
	 * @param int $warnings The number of warnings. Default is 0.
	 * @param int $errors The number of errors. Default is 0.
	 *
	 * @return string A formatted and colorized message string for warnings and errors, or an empty string if neither is provided.
	 */
	private function get_colrefull_warning_errors( $warnings = 0, $errors = 0 ) {
		$message = '';

		if ( $warnings ) {
			// translators: %s: number of warnings.
			$message .= sprintf( __( 'Warnings: %s', 'backwpup' ), '%y' . $warnings . '%n' );
		}

		if ( $errors ) {
			if ( $message ) {
				$message .= ' ';
			}
			// translators: %s: number of errors.
			$message .= sprintf( __( 'Errors: %s', 'backwpup' ), '%r' . $errors . '%n' );
		}

		if ( $message ) {
			return WP_CLI::colorize( $message );
		}

		return '';
	}

	/**
	 * Retrieves the command name.
	 *
	 * @inheritDoc
	 */
	public function get_name(): string {
		return 'status';
	}

	/**
	 * Retrieves the arguments for the command.
	 *
	 * @inheritDoc
	 */
	public function get_args(): array {
		return [];
	}
}
