<?php

namespace WPMedia\BackWPup\Cli\Commands;

use WP_CLI\Formatter;
use WPMedia\BackWPup\Adapters\CronAdapter;
use WPMedia\BackWPup\Adapters\JobAdapter;
use WPMedia\BackWPup\Adapters\OptionAdapter;

class JobActivate implements Command {

	/**
	 * The job adapter instance.
	 *
	 * @var JobAdapter
	 */
	private JobAdapter $job_adapter;

	/**
	 * The option adapter instance.
	 *
	 * @var OptionAdapter
	 */
	private OptionAdapter $option_adapter;

	/**
	 * The Cron adapter instance.
	 *
	 * @var CronAdapter
	 */
	private CronAdapter $cron_adapter;

	/**
	 * Constructor method.
	 *
	 * @param JobAdapter    $job_adapter The job adapter instance.
	 * @param OptionAdapter $option_adapter The option adapter instance.
	 * @param CronAdapter   $cron_adapter The Cron adapter instance.
	 *
	 * @return void
	 */
	public function __construct( JobAdapter $job_adapter, OptionAdapter $option_adapter, CronAdapter $cron_adapter ) {
		$this->job_adapter    = $job_adapter;
		$this->option_adapter = $option_adapter;
		$this->cron_adapter   = $cron_adapter;
	}

	/**
	 * Activate Job (also legacy); Filter by all or selected job IDs
	 *
	 * ## OPTIONS
	 *
	 * [--type=<type>]
	 * : the active type the job should have.
	 * ---
	 * default: wpcron
	 * options:
	 *  - wpcron
	 *  - link
	 *  - disable
	 * ---
	 *
	 * [--job_id=<job_id>]
	 * : Comma-separated list of job IDs to activate. (default all jobs))
	 *
	 * ## EXAMPLES
	 *
	 *     # Activate jobs with ID 1, 2 and 3
	 *     $ wp backwpup job-activate --job_id=1,2,3
	 *     Successes: Job with ID 1 changed to active type wpcron
	 *     Successes: Job with ID 2 changed to active type wpcron
	 *     Successes: Job with ID 4 changed to active type wpcron

	 *     # Deactivate job with ID 3
	 *     $ wp backwpup job-activate --job_id=3 --type=disable
	 *     Successes: Job with ID 3 deactivated
	 *
	 * @alias activate-legacy-job
	 *
	 * @param array $args Positional arguments passed to the method.
	 * @param array $assoc_args Associative arguments passed to the method.
	 *
	 * @return void
	 */
	public function __invoke( array $args, array $assoc_args ) {
		$job_ids = isset( $assoc_args['job_id'] ) ? array_map( 'intval', explode( ',', $assoc_args['job_id'] ) ) : [];
		$type    = $assoc_args['type'] ?? 'wp-cron';
		if ( 'disable' === $type ) {
			$type = '';
		}
		$backup_now_id = get_site_option( 'backwpup_backup_now_job_id', 0 );
		$jobs          = $this->job_adapter->get_jobs();
		$done_job_ids  = [];
		foreach ( $jobs as $job ) {
			if ( $job['jobid'] === $backup_now_id || ! empty( $job['tempjob'] ) || ! empty( $job['backup_now'] ) ) {
				continue;
			}
			if ( $job_ids && ! in_array( $job['jobid'], $job_ids, true ) ) {
				continue;
			}

			if ( ! isset( $job['cron'] ) ) {
				$job['cron'] = '0 0 1 * *';
				$this->option_adapter->update( $job['jobid'], 'cron', $job['cron'] );
				$this->option_adapter->update( $job['jobid'], 'frequency', 'monthly' );
			}

			if ( ! isset( $job['activetype'] ) || $job['activetype'] !== $type ) {
				$job['activetype'] = $type;
				$this->option_adapter->update( $job['jobid'], 'activetype', $type );
			}

			if ( '' === $type ) {
				$this->option_adapter->update( $job['jobid'], 'activ', false );
			} elseif ( empty( $job['activ'] ) ) {
				$this->option_adapter->update( $job['jobid'], 'activ', true );
			}

			if ( 'wpcron' === $type ) {
				wp_schedule_single_event( $this->cron_adapter->cron_next( $job['cron'] ), 'backwpup_cron', [ 'arg' => $job['jobid'] ] );
			} else {
				wp_unschedule_event( $this->cron_adapter->cron_next( $job['cron'] ), 'backwpup_cron', [ 'arg' => $job['jobid'] ] );
			}

			$done_job_ids[] = $job['jobid'];

			if ( ! $type ) {
				// translators: %1$d: Job ID.
				\WP_CLI::success( sprintf( __( 'Job with ID %1$d deactivated', 'backwpup' ), $job['jobid'] ) );
				continue;
			}
			// translators: %1$d: Job ID. %2$s: Active type.
			\WP_CLI::success( sprintf( __( 'Job with ID %1$d changed to active type %2$s', 'backwpup' ), $job['jobid'], $type ) );
		}

		if ( ! $done_job_ids ) {
			\WP_CLI::error( __( 'No jobs found!', 'backwpup' ) );
		}
	}

	/**
	 * Retrieves the command name.
	 *
	 * @inheritDoc
	 */
	public function get_name(): string {
		return 'job-activate';
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
