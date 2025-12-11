<?php

namespace WPMedia\BackWPup\Cli\Commands;

use BackWPup_File;
use WP_CLI;
use WPMedia\BackWPup\Cli\Helpers\CommandsLogHelpersTrait;

/**
 * WP-CLI command for BackWPup: delete log files.
 *
 * Responsibilities:
 * - Delete a specific log by filename.
 * - Delete logs older than a given relative time (e.g., "7 days").
 * - Delete all logs, with optional --dry-run preview.
 *
 * Outputs:
 * - Progress and summary messages to stdout.
 *
 * Notes:
 * - Requires capability: backwpup_logs_delete.
 * - Handles .html and .html.gz files.
 * - Uses WordPress APIs (wp_is_writable/wp_delete_file) instead of direct PHP functions.
 * - If a file is provided (positional or --file), it always takes precedence over --job_id.
 */
class LogDelete implements Command {

	use CommandsLogHelpersTrait;

	/**
	 * Constructor.
	 *
	 * @param BackWPup_File $backwpup_file BackWPup file helper used to resolve absolute paths.
	 */
	public function __construct( BackWPup_File $backwpup_file ) {
		$this->init_log_folder( $backwpup_file );
	}

	/**
	 * Command name used by WP-CLI.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'log-delete';
	}

	/**
	 * Argument schema for registration (not used; options are documented below).
	 *
	 * @return array
	 */
	public function get_args(): array {
		return [];
	}

	/**
	 * Delete BackWPup log files.
	 *
	 * ## OPTIONS
	 *
	 * [<file>]
	 * : Delete a single file (basename; extension optional).
	 *
	 * [--file=<file>]
	 * : Alternative to positional <file>.
	 *
	 * [--older-than=<relative-time>]
	 * : Delete logs older than a relative time, e.g., "7 days", "48 hours".
	 *
	 * [--all]
	 * : Delete all logs.
	 *
	 * [--yes]
	 * : Don't ask for confirmation.
	 *
	 * ## EXAMPLES
	 *
	 *     # Delete a single log file.
	 *     $ wp backwpup log-delete --file=backwpup_log_8a8bee_2025-11-03_06-30-29.html
	 *     Confirm: Delete logfile backwpup_log_8a8bee_2025-11-03_06-30-29.html?
	 *     1 file(s) deleted.
	 *
	 *     # Delete logs older than 30 days.
	 *     $ wp backwpup log-delete --older-than="30 days"
	 *     Confirm: Delete 11 logfile(s)?
	 *     11 file(s) deleted.
	 *
	 * @alias logs-delete
	 *
	 * @param array $args       Positional arguments (unused).
	 * @param array $assoc_args Associative options (see OPTIONS).
	 *
	 * @return void
	 */
	public function __invoke( array $args, array $assoc_args ) {
		$dry = isset( $assoc_args['dry-run'] );

		// When <file> or --file is provided.
		$filename = null;
		if ( ! empty( $args ) && is_string( $args[0] ?? null ) && '' !== $args[0] ) {
			$filename = $args[0];
		} elseif ( isset( $assoc_args['file'] ) && is_string( $assoc_args['file'] ) && '' !== $assoc_args['file'] ) {
			$filename = $assoc_args['file'];
		}

		$has_age  = ! empty( $assoc_args['older-than'] );
		$has_all  = ! empty( $assoc_args['all'] );
		$has_file = ( null !== $filename );

		// Conflict checks.
		if ( $has_file && $has_all ) {
			WP_CLI::warning( __( 'Ignoring --all because a <file> argument was provided.', 'backwpup' ) );
		}
		if ( $has_file && $has_age ) {
			WP_CLI::warning( __( 'Ignoring --older-than because a <file> argument was provided.', 'backwpup' ) );
		}

		// Single-file mode.
		if ( $has_file ) {
			$target = $this->resolve_filename( basename( (string) $filename ) );
			if ( ! isset( $assoc_args['yes'] ) ) {
				/* translators: %s: Backup file name. %s: Storage name. */
				\WP_CLI::confirm( sprintf( __( 'Delete logfile %1$s ?', 'backwpup' ), $filename ) );
			}
			$ok = $this->delete_one( $target );
			WP_CLI::success( $ok ? __( '1 logfile deleted.', 'backwpup' ) : __( 'No logfile deleted.', 'backwpup' ) );
			return;
		}

		// Bulk mode: enumerate logs.
		$files = $this->enumerate_log_files();
		if ( empty( $files ) ) {
			WP_CLI::log( __( 'No logfiles found.', 'backwpup' ) );
			return;
		}

		$to_delete = [];

		if ( $has_all ) {
			$to_delete = array_values( $files );
		} elseif ( $has_age ) {
			$cut = strtotime( 'now - ' . (string) $assoc_args['older-than'] );
			if ( ! $cut ) {
				WP_CLI::error( __( 'Invalid --older-than value.', 'backwpup' ) );
				return;
			}
			foreach ( $files as $mtime_with_key => $name ) {
				$mtime = (int) $mtime_with_key;
				if ( $mtime < $cut ) {
					$to_delete[] = $name;
				}
			}
		} else {
			WP_CLI::error( __( 'Provide <file>/--file, or --older-than, or --all.', 'backwpup' ) );
			return;
		}

		if ( empty( $to_delete ) ) {
			WP_CLI::log( __( 'No logs match the criteria.', 'backwpup' ) );
			return;
		}

		if ( ! isset( $assoc_args['yes'] ) ) {
			/* translators: %s: Backup file name. %s: Storage name. */
			\WP_CLI::confirm( sprintf( __( 'Delete %d logfile(s) ?', 'backwpup' ), count( $to_delete ) ) );
		}

		$count = 0;
		foreach ( $to_delete as $name ) {
			if ( $this->delete_one( $name, $dry ) ) {
				++$count;
			}
		}

		// translators: %d = number of logfiles.
		WP_CLI::success( sprintf( __( '%d logfile(s) deleted.', 'backwpup' ), $count ) );
	}

	/**
	 * Resolve a filename, allowing extension-less input.
	 *
	 * @param string $input User-provided name.
	 * @return string Final filename present on disk.
	 */
	private function resolve_filename( string $input ): string {
		$name        = basename( (string) $input );
		$has_html    = substr( $name, -5 ) === '.html';
		$has_html_gz = substr( $name, -8 ) === '.html.gz';

		// Allow passing filename without extension.
		if ( ! $has_html && ! $has_html_gz ) {
			if ( is_readable( $this->log_folder . '/' . $name . '.html' ) ) {
				$name .= '.html';
			} elseif ( is_readable( $this->log_folder . '/' . $name . '.html.gz' ) ) {
				$name .= '.html.gz';
			}
		}

		$path = $this->log_folder . '/' . $name;

		if ( ! is_readable( $path ) ) {
			WP_CLI::error( 'Log file not found or unreadable: ' . $input );
			return '';
		}

		return $name;
	}

	/**
	 * Delete a single file (or simulate with --dry-run).
	 *
	 * @param string $filename Basename located inside the 'logs' folder.
	 * @return bool  True on success or dry-run, false on failure.
	 */
	private function delete_one( string $filename ): bool {
		$path = $this->log_folder . '/' . basename( $filename );

		if ( ! is_file( $path ) && is_link( $path ) ) {
			// translators: %s: file name.
			WP_CLI::warning( sprintf( __( 'Logfile %s Not found or not a File.', 'backwpup' ), $filename ) );
			return false;
		}

		if ( ! wp_is_writable( $path ) ) {
			// translators: %s: file name.
			WP_CLI::warning( sprintf( __( 'Logfile %s is not writable. Check permissions!', 'backwpup' ), $filename ) );
			return false;
		}

		$deleted = wp_delete_file( $path );

		if ( $deleted ) {
			// translators: %s: file name.
			WP_CLI::log( sprintf( __( 'Logfile %s Deleted.', 'backwpup' ), $filename ) );
			return true;
		}

		// translators: %s: file name.
		WP_CLI::warning( sprintf( __( 'Could not delete logfile %s.', 'backwpup' ), $filename ) );
		return false;
	}
}
