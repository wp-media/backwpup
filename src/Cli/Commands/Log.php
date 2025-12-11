<?php

namespace WPMedia\BackWPup\Cli\Commands;

use BackWPup_File;
use WP_CLI;
use WP_CLI\Utils;
use WPMedia\BackWPup\Cli\Helpers\CommandsLogHelpersTrait;

/**
 * WP-CLI command for BackWPup: list logs.
 *
 * Responsibilities:
 * - Read the BackWPup log directory and extract metadata via BackWPup_Job::read_logheader().
 * - Apply filters (search, job_id, type, date range), custom ordering, and limiting.
 * - Render output in multiple formats (table, csv, json, yaml); humanize bytes/seconds for table/csv.
 *
 * Outputs:
 * - Table/CSV/JSON/YAML with fields:
 *   time_str, job, status, type, size, runtime, errors, warnings, file, job_id
 *
 * Notes:
 * - Terminal-first; no HTML rendering and no WP_List_Table usage.
 * - Matches wp-admin behavior for default ordering (mtime desc) and metadata source.
 * - Supports both .html and .html.gz log files.
 */
class Log implements Command {

	use CommandsLogHelpersTrait;

	/**
	 * Initialize the log folder location.
	 *
	 * @param BackWPup_File $backwpup_file BackWPup file helper used to resolve absolute paths.
	 */
	public function __construct( BackWPup_File $backwpup_file ) {
		$this->init_log_folder( $backwpup_file );
	}

	/**
	 * WP-CLI command name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'log';
	}

	/**
	 * WP-CLI argument schema (not used — options documented in the docblock).
	 *
	 * @return array
	 */
	public function get_args(): array {
		return []; // Registered via WP_CLI::add_command.
	}

	/**
	 * List BackWPup logs with powerful filters and customizable output formats.
	 *
	 * ## OPTIONS
	 *
	 * [--search=<pattern>]
	 * : Free-text filter matching job, name, type, or status.
	 *
	 * [--job_id=<number>]
	 * : Filter logs by job ID.
	 *
	 * [--type=<types>]
	 * : Filter by log type(s), comma-separated. Example: FILE,DBDUMP
	 *
	 * [--after=<date>]
	 * : Include logs on or after the given date (YYYY-MM-DD).
	 *
	 * [--before=<date>]
	 * : Include logs on or before the given date (YYYY-MM-DD).
	 *
	 * [--orderby=<field>]
	 * : Sort by a field.
	 * ---
	 * default: time
	 * options:
	 *  - time
	 *  - job
	 *  - status
	 *  - size
	 *  - runtime
	 * ---
	 *
	 * [--order=<asc|desc>]
	 * : Sort direction.
	 * ---
	 * default: desc
	 * ---
	 *
	 * [--limit=<number>]
	 * : Limit number of results returned.
	 *
	 * [--fields=<fields>]
	 * : Comma-separated list of fields to show.
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *  - table
	 *  - csv
	 *  - json
	 *  - yaml
	 *  - count
	 * ---
	 *
	 * ## AVAILABLE FIELDS
	 * These fields will be displayed by default for each job:
	 * - time
	 * - job
	 * - status
	 * - size
	 * - runtime
	 * - name
	 * - type
	 *
	 * These fields are optionally available:
	 * - timestamp
	 * - job_id
	 * - errors
	 * - warnings
	 *
	 * ## EXAMPLES
	 *
	 *     # View logs as a table (default):
	 *     $ wp backwpup log
	 *     +----------------------------------------------+-----------------------------+-----------------------+------------------------+-----------+---------+----------+
	 *     | name                                         | time                        | job                   | type                   | size      | runtime | status   |
	 *     +----------------------------------------------+-----------------------------+-----------------------+------------------------+-----------+---------+----------+
	 *     | backwpup_log_440bd7_2025-11-10_13-24-36.html | November 10, 2025 @ 1:24 pm | Files & Database      | file, dbdump, wpplugin | 278.50 MB | 2 s     | O.K.     |
	 *     | backwpup_log_e5672b_2025-11-10_13-23-08.html | November 10, 2025 @ 1:23 pm | Files & Database      | file, dbdump, wpplugin | 181.57 MB | 74 s    | 2 ERRORS |
	 *     | backwpup_log_e68e31_2025-11-07_09-22-51.html | November 7, 2025 @ 9:22 am  | Files & Database      | file, dbdump, wpplugin | 278.48 MB | 3 s     | O.K.     |
	 *     | backwpup_log_05a7f7_2025-11-07_09-22-34.html | November 7, 2025 @ 9:22 am  | Files & Database      | file, dbdump, wpplugin |           | 0 s     | O.K.     |
	 *     | backwpup_log_459409_2025-11-07_08-57-28.html | November 7, 2025 @ 8:57 am  | Files & Database      | file, dbdump, wpplugin |           | 0 s     | O.K.     |
	 *     | backwpup_log_6f3f67_2025-11-07_08-57-11.html | November 7, 2025 @ 8:57 am  | Files & Database      | file, dbdump, wpplugin | 278.48 MB | 2 s     | O.K.     |
	 *     | backwpup_log_fa5f45_2025-11-07_08-56-03.html | November 7, 2025 @ 8:56 am  | Files & Database      | file, dbdump, wpplugin | 181.56 MB | 47 s    | 2 ERRORS |
	 *
	 *     # Filter logs by text:
	 *     $ wp backwpup logs --search=backup
	 *     +----------------------------------------------+-----------------------------+------------+------------------------+-----------+---------+--------+
	 *     | name                                         | time                        | job        | type                   | size      | runtime | status |
	 *     +----------------------------------------------+-----------------------------+------------+------------------------+-----------+---------+--------+
	 *     | backwpup_log_ac7889_2025-11-01_10-16-28.html | November 1, 2025 @ 10:16 am | Backup Now | file, dbdump, wpplugin | 282.68 MB | 5 s     | O.K.   |
	 *     +----------------------------------------------+-----------------------------+------------+------------------------+-----------+---------+--------+
	 *
	 *     # Show only logs for job ID 3:
	 *     $ wp backwpup logs --job_id=3
	 *     +----------------------------------------------+-----------------------------+------------------+------------------------+-----------+---------+----------+
	 *     | name                                         | time                        | job              | type                   | size      | runtime | status   |
	 *     +----------------------------------------------+-----------------------------+------------------+------------------------+-----------+---------+----------+
	 *     | backwpup_log_e5672b_2025-11-10_13-23-08.html | November 10, 2025 @ 1:23 pm | Files & Database | file, dbdump, wpplugin | 181.57 MB | 74 s    | 2 ERRORS |
	 *     | backwpup_log_05a7f7_2025-11-07_09-22-34.html | November 7, 2025 @ 9:22 am  | Files & Database | file, dbdump, wpplugin |           | 0 s     | O.K.     |
	 *     | backwpup_log_459409_2025-11-07_08-57-28.html | November 7, 2025 @ 8:57 am  | Files & Database | file, dbdump, wpplugin |           | 0 s     | O.K.     |
	 *     | backwpup_log_fa5f45_2025-11-07_08-56-03.html | November 7, 2025 @ 8:56 am  | Files & Database | file, dbdump, wpplugin | 181.56 MB | 47 s    | 2 ERRORS |
	 *     | backwpup_log_a09990_2025-11-07_08-38-26.html | November 7, 2025 @ 8:38 am  | Files & Database | file, dbdump, wpplugin | 181.56 MB | 76 s    | O.K.     |
	 *     | backwpup_log_8f7ac2_2025-11-06_13-50-50.html | November 6, 2025 @ 1:50 pm  | Files & Database | file, dbdump, wpplugin | 181.56 MB | 82 s    | O.K.     |
	 *
	 *     # List logs after a date sorted by size:
	 *     $ wp backwpup logs --after=2025-01-01 --orderby=size --order=desc
	 *     +----------------------------------------------+-----------------------------+-----------------------+------------------------+-----------+---------+----------+
	 *     | name                                         | time                        | job                   | type                   | size      | runtime | status   |
	 *     +----------------------------------------------+-----------------------------+-----------------------+------------------------+-----------+---------+----------+
	 *     | backwpup_log_ac7889_2025-11-01_10-16-28.html | November 1, 2025 @ 10:16 am | Backup Now            | file, dbdump, wpplugin | 282.68 MB | 5 s     | O.K.     |
	 *     | backwpup_log_727958_2025-11-05_07-10-18.html | November 5, 2025 @ 7:10 am  | Files & Database      | file, dbdump, wpplugin | 278.50 MB | 2 s     | O.K.     |
	 *     | backwpup_log_440bd7_2025-11-10_13-24-36.html | November 10, 2025 @ 1:24 pm | Files & Database      | file, dbdump, wpplugin | 278.50 MB | 2 s     | O.K.     |
	 *     | backwpup_log_e68e31_2025-11-07_09-22-51.html | November 7, 2025 @ 9:22 am  | Files & Database      | file, dbdump, wpplugin | 278.48 MB | 3 s     | O.K.     |
	 *     | backwpup_log_6f3f67_2025-11-07_08-57-11.html | November 7, 2025 @ 8:57 am  | Files & Database      | file, dbdump, wpplugin | 278.48 MB | 2 s     | O.K.     |
	 *     | backwpup_log_47facf_2025-10-24_14-12-58.html | October 24, 2025 @ 2:12 pm  | Files & Database      | file, dbdump, wpplugin | 182.15 MB | 61 s    | O.K.     |
	 *
	 *     # Output as JSON:
	 *     $ wp backwpup logs --format=json
	 *     [{"name":"backwpup_log_440bd7_2025-11-10_13-24-36.html","time":"November 10, 2025 @ 1:24 pm","job":"Files & Database","type":"file, dbdump, wpplugin","size":"292023322","runtime":2,"status":"O.K."},{"name":"backwpup_log_e5672b_2025-11-10_13-23-08.html","time":"November 10, 2025 @ 1:23 pm","job":"Files & Database","type":"file, dbdump, wpplugin","size":"190387274","runtime":74,"status":"2 ERRORS"},{"name":"backwpup_log_e68e31_2025-11-07_09-22-51.html","time":"November 7, 2025 @ 9:22 am","job":"Files & Database","type":"file, dbdump, wpplugin","size":"292007450","runtime":3,"status":"O.K."},{"name":"backwpup_log_05a7f7_2025-11-07_09-22-34.html","time":"November 7, 2025 @ 9:22 am","job":"Files & Database","type":"file, dbdump, wpplugin","size":"0","runtime":0,"status":"O.K."},{"name":"backwpup_log_459409_2025-11-07_08-57-28.html","time":"November 7, 2025 @ 8:57 am","job":"Files & Database","type":"file, dbdump, wpplugin","size":"0","runtime":0,"status":"O.K."}]
	 *
	 * @alias logs
	 *
	 * @param array $args       Positional arguments (unused).
	 * @param array $assoc_args Associative options (see OPTIONS).
	 *
	 * @return void
	 */
	public function __invoke( array $args, array $assoc_args ) {
		$files = $this->enumerate_log_files();

		if ( empty( $files ) ) {
			WP_CLI::log( 'No logs found.' );
			return;
		}

		// Sort newest first (mtime desc), matching wp-admin behavior.
		krsort( $files );

		$rows = [];
		foreach ( $files as $mtime => $filename ) {
			$rows[] = $this->read_log_header( $filename );
		}

		// Free-text search.
		if ( ! empty( $assoc_args['search'] ) ) {
			$q    = (string) $assoc_args['search'];
			$rows = array_values(
				array_filter(
				$rows,
				static function ( $r ) use ( $q ) {
					$job    = isset( $r['job'] ) ? (string) $r['job'] : '';
					$file   = isset( $r['file'] ) ? (string) $r['file'] : '';
					$type   = isset( $r['type'] ) ? (string) $r['type'] : '';
					$status = isset( $r['status'] ) ? (string) $r['status'] : '';

					return ( false !== stripos( $job, $q ) )
					|| ( false !== stripos( $file, $q ) )
					|| ( false !== stripos( $type, $q ) )
					|| ( false !== stripos( $status, $q ) );
				}
				)
				);
		}

		// Filter by job ID.
		if ( isset( $assoc_args['job_id'] ) ) {
			$want = (int) $assoc_args['job_id'];
			$rows = array_values( array_filter( $rows, fn( $r ) => (int) $r['job_id'] === $want ) );
		}

		// Filter by type(s).
		if ( ! empty( $assoc_args['type'] ) ) {
			$want_types = array_map( 'trim', explode( ',', (string) $assoc_args['type'] ) );
			$rows       = array_values(
				array_filter(
					$rows,
					function ( $r ) use ( $want_types ) {
						$types = array_map( 'trim', explode( ',', $r['type'] ) );
						return count( array_intersect( $types, $want_types ) ) > 0;
					}
				)
			);
		}

		// Date filters.
		$after  = ! empty( $assoc_args['after'] ) ? strtotime( $assoc_args['after'] . ' 00:00:00' ) : null;
		$before = ! empty( $assoc_args['before'] ) ? strtotime( $assoc_args['before'] . ' 23:59:59' ) : null;

		if ( $after || $before ) {
			$rows = array_values(
				array_filter(
					$rows,
					fn( $r ) => ( ! $after || $r['timestamp'] >= $after ) && ( ! $before || $r['timestamp'] <= $before )
				)
			);
		}

		// Custom ordering.
		$orderby       = $assoc_args['orderby'] ?? 'time';
		$order         = strtolower( $assoc_args['order'] ?? 'desc' );
		$valid_orderby = [ 'job', 'status', 'size', 'runtime', 'errors', 'warnings', 'name', 'job_id', 'time', 'timestamp', 'type' ];
		if ( ! in_array( $orderby, $valid_orderby, true ) ) {
			$orderby = 'time';
		}
		if ( 'time' === $orderby ) {
			$orderby = 'timestamp';
		}

		usort(
			$rows,
			fn( $a, $b ) => ( 'asc' === $order )
				? ( $a[ $orderby ] <=> $b[ $orderby ] )
				: ( $b[ $orderby ] <=> $a[ $orderby ] )
		);

		// Limit output.
		if ( ! empty( $assoc_args['limit'] ) && is_numeric( $assoc_args['limit'] ) ) {
			$rows = array_slice( $rows, 0, (int) $assoc_args['limit'] );
		}

		// Determine output fields.
		$default_fields = [ 'name', 'time', 'job', 'type', 'size', 'runtime', 'status' ];
		$fields         = ! empty( $assoc_args['fields'] )
			? array_map( 'trim', explode( ',', (string) $assoc_args['fields'] ) )
			: $default_fields;

		$format = $assoc_args['format'] ?? 'table';

		$print_rows = array_map(
			function ( $r ) {
				$r['size']    = is_numeric( $r['size'] ) ? size_format( (float) $r['size'], 2 ) : $r['size'];
				$r['runtime'] = is_numeric( $r['runtime'] ) ? $r['runtime'] . ' s' : $r['runtime'];
				return $r;
			},
			$rows
		);

		Utils\format_items( $format, $print_rows, $fields );
	}
}
