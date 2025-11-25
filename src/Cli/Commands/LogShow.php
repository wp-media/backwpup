<?php

namespace WPMedia\BackWPup\Cli\Commands;

use BackWPup_File;
use WP_CLI;
use WPMedia\BackWPup\Cli\Helpers\CommandsLogHelpersTrait;

/**
 * Command: wp backwpup log-show
 *
 * WP-CLI command for BackWPup: show a single log in plain text.
 *
 * Responsibilities:
 * - Resolve a log file by explicit filename or by job ID (latest).
 * - Read and render the log body as plain text (strip HTML, convert <br> to newlines).
 * - Support tail-like output with --lines to show only the last N lines.
 *
 * Outputs:
 * - Plain text to stdout.
 *
 * Notes:
 * - No HTML rendering; terminal-friendly only.
 * - Supports .html and .html.gz transparently.
 * - Respects BackWPup’s log storage configuration.
 */
class LogShow implements Command {

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
		return 'log-show';
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
	 * Show a BackWPup log as plain text (HTML ignored).
	 *
	 * ## OPTIONS
	 *
	 * [<file>]
	 * : Basename inside the log folder. Extension optional (e.g., backwpup_log_XXXX_YYYY.html or .html.gz).
	 *
	 * [--file=<file>]
	 * : Alternative to positional <file>. Extension optional.
	 *
	 * [--job_id=<number>]
	 * : If no <file>/--file is provided, select the latest log for a given job ID.
	 *
	 * [--lines=<number>]
	 * : Print only the last N lines (tail-like).
	 *
	 * ## EXAMPLES
	 *
	 *     # Show by positional file:
	 *     $ wp backwpup log-show backwpup_log_8a8bee_2025-11-03_06-30-29.html
	 *     BackWPup log for Files & Database from November 7, 2025 at 8:57 am
	 *     [INFO] BackWPup 5.6.0; A project of WP Media
	 *     [INFO] WordPress 6.8.3 on https://backwpup-pro.ddev.site/
	 *     [INFO] Log Level: Debug
	 *     [INFO] BackWPup job: Files & Database; FILE+DBDUMP+WPPLUGIN
	 *     [INFO] Runs with user:  (0)
	 *
	 *     # Show by filename:
	 *     $ wp backwpup log-show --file=backwpup_log_8a8bee_2025-11-03_06-30-29.html
	 *     [INFO] BackWPup 5.6.0; A project of WP Media
	 *     [INFO] WordPress 6.8.3 on https://backwpup-pro.ddev.site/
	 *     [INFO] Log Level: Debug
	 *     [INFO] BackWPup job: Files & Database; FILE+DBDUMP+WPPLUGIN
	 *     [INFO] Runs with user:  (0)
	 *
	 *     # Show only the last 200 lines:
	 *     $ wp backwpup log-show --file=backwpup_log_... --lines=14
	 *     [07-Nov-2025 08:57:36] Archiving Folder: /var/www/html/.ddev/wordpress/wp-includes/sodium_compat/src/Core32/SecretStream/
	 *     [07-Nov-2025 08:57:36] Archiving Folder: /var/www/html/.ddev/wordpress/wp-includes/sodium_compat/src/PHP52/
	 *     [07-Nov-2025 08:57:36] Archiving Folder: /var/www/html/.ddev/wordpress/wp-includes/style-engine/
	 *     [07-Nov-2025 08:57:36] Archiving Folder: /var/www/html/.ddev/wordpress/wp-includes/theme-compat/
	 *     [07-Nov-2025 08:57:36] Archiving Folder: /var/www/html/.ddev/wordpress/wp-includes/widgets/
	 *     [07-Nov-2025 08:57:36] Backup archive created.
	 *     [07-Nov-2025 08:57:36] Archive size is 181.56 MB.
	 *     [07-Nov-2025 08:57:36] 8657 Files with 272.06 MB in Archive.
	 *     [07-Nov-2025 08:57:36] Restart after 8 seconds.
	 *     [07-Nov-2025 08:57:36] 1. Trying to encrypt archive …
	 *     [07-Nov-2025 08:57:37] Encrypted 181.63 MB of data.
	 *     [07-Nov-2025 08:57:37] Archive has been successfully encrypted.
	 *     [07-Nov-2025 08:57:37] 1. Try to send backup file to HiDrive …
	 *     [07-Nov-2025 08:58:04] Restart after 28 seconds.
	 *
	 * @alias logs-show
	 *
	 * @param array $args       Positional arguments (unused).
	 * @param array $assoc_args Associative options (see OPTIONS).
	 *
	 * @return void
	 */
	public function __invoke( array $args, array $assoc_args ) {
		$filename = null;
		if ( ! empty( $args ) && is_string( $args[0] ?? null ) && '' !== $args[0] ) {
			$filename = $args[0];
		} elseif ( isset( $assoc_args['file'] ) && is_string( $assoc_args['file'] ) && '' !== $assoc_args['file'] ) {
			$filename = $assoc_args['file'];
		}

		if ( $filename ) {
			// Harden: only allow basenames. Extension inference happens inside read_log_plaintext().
			$filename = basename( (string) $filename );
		} elseif ( isset( $assoc_args['job_id'] ) ) {
			// Resolve by job_id (pick latest) when no explicit file is provided.
			$files = $this->enumerate_log_files();
			if ( empty( $files ) ) {
				WP_CLI::error( __( 'No logs found.', 'backwpup' ) );
			}

			$candidates = [];
			foreach ( $files as $mtime => $name ) {
				$hdr = $this->read_log_header( $name );
				if ( (int) $hdr['job_id'] === (int) $assoc_args['job_id'] ) {
					$candidates[ $mtime ] = $name;
				}
			}

			if ( empty( $candidates ) ) {
				WP_CLI::error( __( 'No logs found for the given job_id.', 'backwpup' ) );
			}

			krsort( $candidates ); // Newest first.
			$filename = reset( $candidates ); // Latest by default.
		} else {
			WP_CLI::error( __( 'Please provide <file>, --file=<file>, or --job_id=<id>.', 'backwpup' ) );
		}

		// Load as plain text (this method infers .html/.html.gz if missing).
		$text_in_lines = $this->read_log_plaintext( $filename );
		$lines         = isset( $assoc_args['lines'] ) ? max( 1, (int) $assoc_args['lines'] ) : null;

		if ( $lines ) {
			$text_in_lines = array_slice( $text_in_lines, -$lines );
		}

		foreach ( $text_in_lines as $line ) {
			WP_CLI::line( $line );
		}
	}
}
