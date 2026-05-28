<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Log;

/**
 * Append HTML log lines to disk.
 */
final class HtmlLogWriter {

	/**
	 * Append a log line to the given file.
	 *
	 * @param string $logfile Log file path.
	 * @param string $html_line HTML line to append.
	 *
	 * @return bool
	 */
	public function append( string $logfile, string $html_line ): bool {
		$filesystem   = backwpup_wpfilesystem();
		$existing_log = $filesystem->get_contents( $logfile );
		$existing_log = false === $existing_log ? '' : $existing_log;

		return (bool) $filesystem->put_contents(
			$logfile,
			$existing_log . $html_line
		);
	}
}
