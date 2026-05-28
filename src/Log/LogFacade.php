<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Log;

/**
 * Facade for log reading, rendering, and formatting.
 */
final class LogFacade {

	/**
	 * Log header reader.
	 *
	 * @var HtmlLogReader
	 */
	private $reader;

	/**
	 * Log HTML renderer.
	 *
	 * @var HtmlLogRenderer
	 */
	private $renderer;

	/**
	 * Log date formatter.
	 *
	 * @var WpDateLogFormatter
	 */
	private $formatter;

	/**
	 * Log excerpt reader.
	 *
	 * @var HtmlLogExcerptReader
	 */
	private $excerpt_reader;

	/**
	 * Log writer.
	 *
	 * @var HtmlLogWriter
	 */
	private $writer;

	/**
	 * Create the log facade.
	 *
	 * @param HtmlLogReader        $reader Log header reader.
	 * @param HtmlLogRenderer      $renderer Log HTML renderer.
	 * @param WpDateLogFormatter   $formatter Log date formatter.
	 * @param HtmlLogExcerptReader $excerpt_reader Log excerpt reader.
	 * @param HtmlLogWriter        $writer Log writer.
	 */
	public function __construct(
		?HtmlLogReader $reader = null,
		?HtmlLogRenderer $renderer = null,
		?WpDateLogFormatter $formatter = null,
		?HtmlLogExcerptReader $excerpt_reader = null,
		?HtmlLogWriter $writer = null
	) {
		$this->reader         = $reader ?? new HtmlLogReader();
		$this->renderer       = $renderer ?? new HtmlLogRenderer();
		$this->formatter      = $formatter ?? new WpDateLogFormatter();
		$this->excerpt_reader = $excerpt_reader ?? new HtmlLogExcerptReader( $this->renderer );
		$this->writer         = $writer ?? new HtmlLogWriter();
	}

	/**
	 * Read normalized log metadata.
	 *
	 * @param string $path Log file path.
	 *
	 * @return array<string, mixed>
	 */
	public function read_header( string $path ): array {
		return $this->reader->read( $path );
	}

	/**
	 * Render log HTML for display.
	 *
	 * @param string $content Raw HTML content.
	 *
	 * @return string
	 */
	public function render_html( string $content ): string {
		return $this->renderer->render( $content );
	}

	/**
	 * Format a UTC timestamp with the site date format.
	 *
	 * @param int $timestamp UTC timestamp.
	 *
	 * @return string
	 */
	public function format_date( int $timestamp ): string {
		return $this->formatter->format_date( $timestamp );
	}

	/**
	 * Format a UTC timestamp with the site time format.
	 *
	 * @param int $timestamp UTC timestamp.
	 *
	 * @return string
	 */
	public function format_time( int $timestamp ): string {
		return $this->formatter->format_time( $timestamp );
	}

	/**
	 * Format a UTC timestamp with the combined site date/time label.
	 *
	 * @param int $timestamp UTC timestamp.
	 *
	 * @return string
	 */
	public function format_datetime( int $timestamp ): string {
		return $this->formatter->format_datetime( $timestamp );
	}

	/**
	 * Read a rendered log excerpt.
	 *
	 * @param string $path Log file path.
	 * @param int    $max_lines Maximum number of lines to return.
	 * @param bool   $truncated Whether the excerpt was truncated.
	 *
	 * @return array<int, string>
	 */
	public function read_excerpt( string $path, int $max_lines, bool &$truncated = false ): array {
		return $this->excerpt_reader->read( $path, $max_lines, $truncated );
	}

	/**
	 * Append a rendered log line to disk.
	 *
	 * @param string $logfile Log file path.
	 * @param string $html_line HTML line to append.
	 *
	 * @return bool
	 */
	public function append( string $logfile, string $html_line ): bool {
		return $this->writer->append( $logfile, $html_line );
	}
}
