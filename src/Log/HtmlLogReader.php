<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Log;

/**
 * Read normalized metadata from HTML log files.
 */
final class HtmlLogReader {

	/**
	 * Read log header metadata and normalize the log timestamp to UTC.
	 *
	 * @param string $path Log file path.
	 *
	 * @return array<string,mixed>
	 */
	public function read( string $path ): array {
		$metas   = $this->parse_meta_tags( $path );
		$logtime = $this->resolve_logtime( $metas, $path );

		return [
			'logtime'        => $logtime,
			'errors'         => (int) ( $metas['backwpup_errors'] ?? 0 ),
			'warnings'       => (int) ( $metas['backwpup_warnings'] ?? 0 ),
			'jobid'          => (int) ( $metas['backwpup_jobid'] ?? 0 ),
			'name'           => (string) ( $metas['backwpup_jobname'] ?? '' ),
			'type'           => (string) ( $metas['backwpup_jobtype'] ?? '' ),
			'runtime'        => (int) ( $metas['backwpup_jobruntime'] ?? 0 ),
			'backupfilesize' => (int) ( $metas['backwpup_backupfilesize'] ?? 0 ),
			'file'           => $path,
		];
	}

	/**
	 * Parse meta tags from a plain or compressed log file.
	 *
	 * @param string $path Log file path.
	 *
	 * @return array<string, string>
	 */
	private function parse_meta_tags( string $path ): array {
		if ( ! is_readable( $path ) ) {
			return [];
		}

		if ( '.gz' === substr( $path, -3 ) ) {
			return (array) get_meta_tags( 'compress.zlib://' . $path );
		}

		return (array) get_meta_tags( $path );
	}

	/**
	 * Resolve the UTC timestamp for a log entry.
	 *
	 * @param array<string, string> $metas Meta tags indexed by name.
	 * @param string                $path  Log file path.
	 *
	 * @return int
	 */
	private function resolve_logtime( array $metas, string $path ): int {
		if ( ! empty( $metas['date'] ) ) {
			$ts = strtotime( $metas['date'] );
			if ( false !== $ts ) {
				return (int) $ts;
			}
		}

		if ( ! empty( $metas['backwpup_logtime'] ) ) {
			return (int) $metas['backwpup_logtime'];
		}

		if ( file_exists( $path ) ) {
			$ctime = filectime( $path );
			if ( false !== $ctime ) {
				return (int) $ctime;
			}
		}

		return time();
	}
}
