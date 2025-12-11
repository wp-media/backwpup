<?php

namespace WPMedia\BackWPup\Cli\Helpers;

use BackWPup_File;
use BackWPup_Job;
use WP_CLI;

/**
 * Shared helpers for BackWPup CLI log commands.
 *
 * Responsibilities:
 * - Resolve the log folder from BackWPup settings (absolute path).
 * - Enumerate log files (.html and .html.gz).
 * - Read and normalize log headers via BackWPup internals.
 * - Load log bodies and render as plain text (strip HTML).
 *
 * Notes:
 * - CLI-only; no HTML/UI concerns.
 * - Uses the same metadata source as the wp-admin UI (read_logheader()).
 */
trait CommandsLogHelpersTrait {

	/**
	 * The log folder.
	 *
	 * @var string Absolute path to the log folder.
	 */
	protected string $log_folder = '';

	/**
	 * Initialize $this->log_folder with the configured absolute path.
	 *
	 * @param BackWPup_File $backwpup_file BackWPup file helper used to resolve absolute paths.
	 * @return void
	 */
	protected function init_log_folder( BackWPup_File $backwpup_file ): void {
		$folder           = get_site_option( 'backwpup_cfg_logfolder' );
		$folder           = $backwpup_file::get_absolute_path( $folder );
		$this->log_folder = untrailingslashit( $folder );
	}

	/**
	 * Iterate over existing log files (.html or .html.gz) and return an array mapping
	 * file modification time (mtime) to filename. Only files starting with
	 * "backwpup_log_" are considered, and only readable files are included.
	 *
	 * @return array<int,string> mtime => filename
	 */
	protected function enumerate_log_files(): array {
		$out = [];

		if ( ! is_readable( $this->log_folder ) ) {
			return $out;
		}

		$dir = new \DirectoryIterator( $this->log_folder );
		foreach ( $dir as $fileinfo ) {
			if ( $fileinfo->isDot() || ! $fileinfo->isFile() || ! $fileinfo->isReadable() ) {
				continue;
			}
			$name      = $fileinfo->getFilename();
			$starts_ok = ( 0 === strpos( $name, 'backwpup_log_' ) );
			$ends_ok   = ( substr( $name, -5 ) === '.html' ) || ( substr( $name, -8 ) === '.html.gz' );
			if ( $starts_ok && $ends_ok ) {
				$out[ $fileinfo->getMTime() . '_' . $name ] = $name;
			}
		}

		return $out;
	}

	/**
	 * Read the log header using BackWPup_Job::read_logheader() and normalize fields.
	 *
	 * @param string $filename File name inside the log folder.
	 * @return array<string,mixed> Fields:
	 *   - time (int, timestamp)
	 *   - time_str (string, site-local formatted)
	 *   - job (string, job name or fallback to file)
	 *   - jobid (int)
	 *   - status (string: OK|WARNING|ERROR)
	 *   - type (string, CSV)
	 *   - size (int|null)
	 *   - runtime (int, seconds)
	 *   - errors (int)
	 *   - warnings (int)
	 *   - file (string, filename)
	 */
	protected function read_log_header( string $filename ): array {
		$path = $this->log_folder . '/' . $filename;
		$hdr  = $this->core_read_logheader( $path );

		$time     = isset( $hdr['logtime'] ) ? (int) $hdr['logtime'] : ( filemtime( $path ) ?: 0 );
		$job      = ! empty( $hdr['name'] ) ? $hdr['name'] : ( $hdr['file'] ?? $filename );
		$type     = isset( $hdr['type'] ) ? strtolower( str_replace( '+', ', ', (string) $hdr['type'] ) ) : '';
		$size     = $hdr['backupfilesize'] ?? null;
		$errors   = (int) ( $hdr['errors'] ?? 0 );
		$warnings = (int) ( $hdr['warnings'] ?? 0 );

		$status = '';
		if ( 0 < $warnings ) {
			// translators: %d: number of warnings.
			$status .= sprintf( _n( '%d WARNING', '%d WARNINGS', $warnings, 'backwpup' ), $warnings );
		}
		if ( 0 < $errors ) {
			if ( $status ) {
				$status .= ', ';
			}
			// translators: %d: number of errors.
			$status .= sprintf( _n( '%d ERROR', '%d ERRORS', $errors, 'backwpup' ), $errors );
		}
		if ( ! $errors && ! $warnings ) {
			$status = __( 'O.K.', 'backwpup' );
		}

		return [
			'name'      => $filename,
			'timestamp' => $time,
			'time'      => sprintf( '%1$s @ %2$s', wp_date( get_option( 'date_format' ), $time, new \DateTimeZone( 'UTC' ) ), wp_date( get_option( 'time_format' ), $time, new \DateTimeZone( 'UTC' ) ) ),
			'job'       => html_entity_decode( $job ),
			'job_id'    => (int) ( $hdr['jobid'] ?? 0 ),
			'status'    => $status,
			'type'      => $type,
			'size'      => $size,
			'runtime'   => (int) ( $hdr['runtime'] ?? 0 ),
			'errors'    => $errors,
			'warnings'  => $warnings,
		];
	}

	/**
	 * Small seam to read headers via core API. Wrapped to ease unit testing without mocking static calls.
	 *
	 * @param string $path Absolute path to log file.
	 * @return array<string,mixed>
	 */
	protected function core_read_logheader( string $path ): array {
		return \BackWPup_Job::read_logheader( $path );
	}

	/**
	 * Load the log body and convert it to plain text.
	 * Supports both .html and .html.gz. All HTML tags are stripped and <br> tags
	 * are converted into newlines to keep terminal readability.
	 *
	 * @param string $filename File name inside the log folder.
	 * @return array Plain-text log content line by line.
	 */
	protected function read_log_plaintext( string $filename ): array {
		$base = $this->log_folder . '/' . $filename;

		$path = null;

		if ( is_readable( $base ) ) {
			$path = $base;
		} elseif ( is_readable( $base . '.html' ) ) {
			$path = $base . '.html';
		} elseif ( is_readable( $base . '.html.gz' ) ) {
			$path = 'compress.zlib://' . $base . '.html.gz';
		}

		if ( null === $path ) {
			// translators: %s: logfile name.
			WP_CLI::error( sprintf( __( 'Logfile %s not found or unreadable.', 'backwpup' ), $filename ) );
		}

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$raw = file_get_contents( $path );
		if ( false === $raw ) {
			// translators: %s: logfile name.
			WP_CLI::error( sprintf( __( 'Failed to read the logfile %s.', 'backwpup' ), $filename ) );
		}

		// strip all remaining HTML.
		$raw = wp_strip_all_tags( $raw );
		$raw = html_entity_decode( $raw );

		// Normalize line breaks to Linux.
		$raw = str_replace( "\r\n", "\n", $raw );

		// remove empty lines.
		$lines = [];
		foreach ( explode( "\n", $raw ) as $line ) {
			$new_line = trim( $line );
			if ( trim( $line ) ) {
				$lines[] = $new_line;
			}
		}

		return $lines;
	}
}
