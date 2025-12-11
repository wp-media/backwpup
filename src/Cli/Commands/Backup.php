<?php

namespace WPMedia\BackWPup\Cli\Commands;

use WPMedia\BackWPup\Adapters\BackWPupAdapter;
use WPMedia\BackWPup\Adapters\JobAdapter;

class Backup implements Command {

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
	 * @param BackWPupAdapter $backwpup_adapter The BackWPup adapter instance.
	 *
	 * @return void
	 */
	public function __construct( JobAdapter $job_adapter, BackWPupAdapter $backwpup_adapter ) {
		$this->job_adapter      = $job_adapter;
		$this->backwpup_adapter = $backwpup_adapter;
	}

	/**
	 * Show BackWPup backup archives.
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
	 * - name
	 * - time
	 * - size
	 * - storage
	 * - type
	 *
	 * These fields are optionally available:
	 * - folder
	 * - file
	 * - job_id
	 * - size_bytes
	 * - time_unix
	 *
	 * ## EXAMPLES
	 *
	 *     # Display a list of backups as a table.
	 *     $ wp backwpup backup
	 *     +------------------------------------------------------------+-----------------------------+-----------+---------+------------------------+
	 *     | name                                                       | time                        | size      | storage | type                   |
	 *     +------------------------------------------------------------+-----------------------------+-----------+---------+------------------------+
	 *     | 2025-11-10_13-24-36_SDIUOYJN09_FILE-DBDUMP-WPPLUGIN.tar    | November 10, 2025 @ 1:24 pm | 278.50 MB | folder  | file, dbdump, wpplugin |
	 *     | 2025-11-10_13-23-08_FHIUOYK706_FILE-DBDUMP-WPPLUGIN.tar.gz | November 10, 2025 @ 1:24 pm | 181.57 MB | hidrive | file, dbdump, wpplugin |
	 *     | 2025-11-10_13-23-08_FHIUOYK706_FILE-DBDUMP-WPPLUGIN.tar.gz | November 10, 2025 @ 1:23 pm | 181.57 MB | folder  | file, dbdump, wpplugin |
	 *     | 2025-11-07_09-22-51_U7IUOYKR09_FILE-DBDUMP-WPPLUGIN.tar    | November 7, 2025 @ 9:22 am  | 278.48 MB | folder  | file, dbdump, wpplugin |
	 *     | 2025-11-07_09-22-34_DXIUOYMY06_FILE-DBDUMP-WPPLUGIN.tar.gz | November 7, 2025 @ 9:22 am  | 181.56 MB | folder  | file, dbdump, wpplugin |
	 *     | 2025-11-07_08-57-28_YHIUOYNL06_FILE-DBDUMP-WPPLUGIN.tar.gz | November 7, 2025 @ 8:58 am  | 88.00 MB  | hidrive | file, dbdump, wpplugin |
	 *     | 2025-11-07_08-57-28_YHIUOYNL06_FILE-DBDUMP-WPPLUGIN.tar.gz | November 7, 2025 @ 8:57 am  | 181.56 MB | folder  | file, dbdump, wpplugin |
	 *     | 2025-11-07_08-57-11_IDIUOYPB09_FILE-DBDUMP-WPPLUGIN.tar    | November 7, 2025 @ 8:57 am  | 278.48 MB | folder  | file, dbdump, wpplugin |
	 *     | 2025-11-07_08-56-03_HLIUOYPE06_FILE-DBDUMP-WPPLUGIN.tar.gz | November 7, 2025 @ 8:57 am  | 112.00 MB | hidrive | file, dbdump, wpplugin |
	 *     | 2025-11-07_08-56-03_HLIUOYPE06_FILE-DBDUMP-WPPLUGIN.tar.gz | November 7, 2025 @ 8:56 am  | 181.56 MB | folder  | file, dbdump, wpplugin |
	 *
	 *     # Display only filename and storage in json format
	 *     $ wp backwpup backup --fields=storage,name --format=json
	 *     [{"storage":"folder","name":"2025-11-10_13-24-36_SDIUOYJN09_FILE-DBDUMP-WPPLUGIN.tar"},{"storage":"hidrive","name":"2025-11-10_13-23-08_FHIUOYK706_FILE-DBDUMP-WPPLUGIN.tar.gz"},{"storage":"folder","name":"2025-11-10_13-23-08_FHIUOYK706_FILE-DBDUMP-WPPLUGIN.tar.gz"}]
	 *
	 * @alias backups
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
			'name',
			'time',
			'size',
			'storage',
			'type',
		];
		if ( isset( $assoc_args['fields'] ) ) {
			$fields = explode( ',', $assoc_args['fields'] );
		}

		$items                   = [];
		$jobs                    = $this->job_adapter->get_jobs();
		$registered_destinations = $this->backwpup_adapter->get_registered_destinations();
		foreach ( $jobs as $job ) {
			if ( ! empty( $job['tempjob'] ) ) {
				continue;
			}

			if ( ! $job['destinations'] ) {
				$job['destinations'] = [];
			}
			foreach ( $job['destinations'] as $destination ) {
				if ( empty( $registered_destinations[ $destination ]['class'] ) ) {
					continue;
				}
				$dest_object = $this->backwpup_adapter->get_destination( $destination );
				if ( is_array( $dest_object ) ) {
					continue;
				}
				$backups = $dest_object->file_get_list( $job['jobid'] . '_' . $destination );

				foreach ( $backups as $backup ) {
					$filename = pathinfo( $backup['filename'] )['filename'];
					if ( stripos( $backup['filename'], '.tar.gz' ) === strlen( $backup['filename'] ) - 7 ) {
						$filename = substr( $backup['filename'], 0, -7 );
					} elseif ( stripos( $backup['filename'], '.tar.bz2' ) === strlen( $backup['filename'] ) - 8 ) {
						$filename = substr( $backup['filename'], 0, -8 );
					}
					$filename_parts = explode( '_', $filename );
					$type           = explode( '-', array_pop( $filename_parts ) );

					$time = sprintf( '%1$s @ %2$s', wp_date( get_option( 'date_format' ), $backup['time'], new \DateTimeZone( 'UTC' ) ), wp_date( get_option( 'time_format' ), $backup['time'], new \DateTimeZone( 'UTC' ) ) );

					$items[ $backup['time'] ] = [
						'name'       => $backup['filename'],
						'folder'     => $backup['folder'],
						'storage'    => strtolower( $destination ),
						'time'       => $time,
						'time_unix'  => $backup['time'],
						'size'       => size_format( $backup['filesize'], 2 ),
						'size_bytes' => $backup['filesize'],
						'type'       => strtolower( implode( ', ', $type ) ),
						'job_id'     => $job['jobid'],
						'file'       => $backup['file'],
					];
				}
			}
		}

		krsort( $items );

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
			$new_items = [];
			foreach ( $items as $item ) {
				if ( ! in_array( $item['name'], $new_items, true ) ) {
					$new_items[] = $item['name'];
				}
			}
			$items = $new_items;
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
		return 'backup';
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
