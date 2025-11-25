<?php

namespace WPMedia\BackWPup\Cli\Commands;

use WP_CLI\Formatter;
use WPMedia\BackWPup\Adapters\JobAdapter;

class Job implements Command {

	/**
	 * The job adapter instance.
	 *
	 * @var JobAdapter
	 */
	private JobAdapter $job_adapter;

	/**
	 * Constructor method.
	 *
	 * @param JobAdapter $job_adapter The job adapter instance.
	 *
	 * @return void
	 */
	public function __construct( JobAdapter $job_adapter ) {
		$this->job_adapter = $job_adapter;
	}

	/**
	 * Show BackWPup jobs.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *  - table
	 *  - json
	 *  - csv
	 *  - yaml
	 *  - count
	 *  - ids
	 * ---
	 *
	 * [--<field>=<value>]
	 * : Filter by one or more fields (see “Available Fields” section).
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each job.
	 *
	 * [--fields=<fields>]
	 * : Comma-separated list of fields to show.
	 *
	 * ## AVAILABLE FIELDS
	 * These fields will be displayed by default for each job:
	 * - job_id
	 * - name
	 * - active_type
	 * - type
	 * - storages
	 * - cron
	 * - last_run
	 * - last_runtime
	 *
	 * These fields are optionally available:
	 * - backup_type
	 * - archive_format
	 * - legacy
	 * - archive_encryption
	 * - email_address_for_logs
	 * - email_on_errors_only
	 *
	 * ## EXAMPLES
	 *
	 *     # Output all jobs as a table
	 *     $ wp backwpup job --field=job_id
	 *     +--------+------------------+------------------------+-----------------+-------------+-----------+----------------------------+--------------+
	 *     | job_id | name             | type                   | storages        | active_type | cron      | last_run                   | last_runtime |
	 *     +--------+------------------+------------------------+-----------------+-------------+-----------+----------------------------+--------------+
	 *     | 6      | Files & Database | file, dbdump, wpplugin | folder, hidrive | wpcron      | 0 0 1 * * | November 7, 2025 @ 9:22 am | 47 Seconds   |
	 *     | 9      | Files & Database | file, dbdump, wpplugin | folder          | wpcron      | 0 0 1 * * | November 7, 2025 @ 9:22 am | 3 Seconds    |
	 *     | 10     | Database         | dbdump                 | folder          | wpcron      | 0 0 1 * * | November 7, 2025 @ 8:51 am |              |
	 *     +--------+------------------+------------------------+-----------------+-------------+-----------+----------------------------+--------------+
	 *
	 *     # Outputs only fields job_id and legacy as json
	 *     $ wp backwpup job --fields=job_id,legacy --format=json
	 *     [{"job_id":6,"legacy":"no"},{"job_id":9,"legacy":"no"},{"job_id":10,"legacy":"no"}]
	 *
	 * @alias jobs
	 *
	 * @param array $args Positional arguments passed to the method.
	 * @param array $assoc_args Associative arguments passed to the method.
	 *
	 * @return void
	 */
	public function __invoke( array $args, array $assoc_args ) {

		$format = 'table';
		if ( isset( $assoc_args['format'] ) && in_array( strtolower( $assoc_args['format'] ), [ 'table', 'json', 'csv', 'yaml', 'count', 'ids' ], true ) ) {
			$format = strtolower( $assoc_args['format'] );
		}

		// Get the list of default fields to display.
		$fields = [
			'job_id',
			'name',
			'type',
			'storages',
			'active_type',
			'cron',
			'last_run',
			'last_runtime',
		];
		if ( isset( $assoc_args['fields'] ) ) {
			$fields = explode( ',', $assoc_args['fields'] );
		}

		// Get the list of jobs.
		$items         = [];
		$backup_now_id = get_site_option( 'backwpup_backup_now_job_id', 0 );
		$jobs          = $this->job_adapter->get_jobs();
		foreach ( $jobs as $job ) {
			if ( $job['jobid'] === $backup_now_id || ! empty( $job['tempjob'] ) || ! empty( $job['backup_now'] ) ) {
				continue;
			}
			if ( ! empty( $job['lastrun'] ) ) {
				$last_run_string = sprintf( '%1$s @ %2$s', wp_date( get_option( 'date_format' ), $job['lastrun'], new \DateTimeZone( 'UTC' ) ), wp_date( get_option( 'time_format' ), $job['lastrun'], new \DateTimeZone( 'UTC' ) ) );
			} else {
				$last_run_string = 'never';
			}

			$items[ $job['jobid'] ] = [
				'job_id'                 => $job['jobid'],
				'name'                   => $job['name'],
				'active_type'            => ! empty( $job['activetype'] ) ? $job['activetype'] : 'none',
				'type'                   => strtolower( implode( ', ', (array) $job['type'] ) ),
				'storages'               => strtolower( implode( ', ', (array) $job['destinations'] ) ),
				'cron'                   => $job['cron'] ?? '0 0 1 * *',
				'backup_type'            => $job['backuptype'],
				'archive_format'         => $job['archiveformat'],
				'last_run'               => $last_run_string,
				'last_runtime'           => ! empty( $job['lastruntime'] ) ? $job['lastruntime'] . ' Seconds' : '',
				'legacy'                 => $job['legacy'] ? 'yes' : 'no',
				'archive_encryption'     => $job['archiveencryption'] ? 'yes' : 'no',
				'email_address_for_logs' => $job['mailaddresslog'],
				'email_on_errors_only'   => $job['mailerroronly'] ? 'yes' : 'no',
			];
		}

		// Filter the list of jobs based on the arguments.
		foreach ( $items as $job_id => $item ) {
			foreach ( $item as $key => $value ) {
				if ( isset( $assoc_args[ $key ] ) && false === stripos( (string) $value, (string) $assoc_args[ $key ] ) ) {
					unset( $items[ $job_id ] );
				}
			}
		}

		// If the --field argument is set, print the value of the specified field for each job.
		if ( isset( $assoc_args['field'] ) ) {
			if ( ! $items ) {
				return;
			}
			$field = strtolower( $assoc_args['field'] );
			if ( ! array_key_exists( $field, $items[ key( $items ) ] ) ) {
				\WP_CLI::error( 'Invalid field: ' . $field );
			}
			foreach ( $items as $item ) {
				\WP_CLI::line( $item[ $field ] );
			}
			return;
		}

		if ( isset( $assoc_args['format'] ) && 'ids' === $assoc_args['format'] ) {
			$items = array_keys( $items );
		}

		// Print the list of jobs.
		\WP_CLI\Utils\format_items( $format, $items, $fields );
	}

	/**
	 * Retrieves the command name.
	 *
	 * @inheritDoc
	 */
	public function get_name(): string {
		return 'job';
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
