<?php

namespace WPMedia\BackWPup\Cli\Commands;

use WPMedia\BackWPup\Adapters\BackWPupAdapter;
use WPMedia\BackWPup\Adapters\JobAdapter;

class Kill implements Command {

	/**
	 * The job adapter instance.
	 *
	 * @var JobAdapter
	 */
	private JobAdapter $job_adapter;
	/**
	 * The BackWPup adapter instance.
	 *
	 * @var BackWPupAdapter
	 */
	private BackWPupAdapter $backwpup_adapter;

	/**
	 * Constructor method.
	 *
	 * @param JobAdapter      $job_adapter The job adapter instance.
	 * @param BAckWPupAdapter $backwpup_adapter The BackWPup adapter instance.
	 *
	 * @return void
	 */
	public function __construct( JobAdapter $job_adapter, BAckWPupAdapter $backwpup_adapter ) {
		$this->job_adapter      = $job_adapter;
		$this->backwpup_adapter = $backwpup_adapter;
	}

	/**
	 * Kills a running BackWPup job.
	 *
	 * ## EXAMPLES
	 *
	 *     # Kill a running job.
	 *     $ wp backwpup kill
	 *     Success: Job will be terminated.
	 *
	 * @alias abort
	 *
	 * @param array $args Positional arguments passed to the method.
	 * @param array $assoc_args Associative arguments passed to the method.
	 *
	 * @return void
	 */
	public function __invoke( array $args, array $assoc_args ) {
		if ( ! file_exists( $this->backwpup_adapter->get_plugin_data( 'running_file' ) ) ) {
			\WP_CLI::success( __( 'Nothing to abort.', 'backwpup' ) );
			return;
		}

		$this->job_adapter->user_abort();
		\WP_CLI::success( __( 'Job will be terminated.', 'backwpup' ) );
	}


	/**
	 * Retrieves the command name.
	 *
	 * @inheritDoc
	 */
	public function get_name(): string {
		return 'kill';
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
