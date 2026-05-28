<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Log;

use BackWPup_File;

/**
 * Read and normalize log excerpts for display.
 */
final class HtmlLogExcerptReader {

	/**
	 * Renderer used to translate timestamp spans before stripping markup.
	 *
	 * @var HtmlLogRenderer
	 */
	private $renderer;

	/**
	 * Create the excerpt reader.
	 *
	 * @param HtmlLogRenderer|null $renderer Optional renderer instance.
	 */
	public function __construct( ?HtmlLogRenderer $renderer = null ) {
		$this->renderer = $renderer ?? new HtmlLogRenderer();
	}

	/**
	 * Read and normalize a log excerpt for display.
	 *
	 * @param string $path Log file path or basename.
	 * @param int    $max_lines Maximum number of lines to return.
	 * @param bool   $truncated Set to true when the excerpt is shortened.
	 *
	 * @return array<int,string>
	 */
	public function read( string $path, int $max_lines, bool &$truncated = false ): array {
		$resolved_path = $this->resolve_path( $path );
		if ( null === $resolved_path ) {
			return [];
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$raw = file_get_contents( $resolved_path );
		if ( false === $raw ) {
			return [];
		}

		$raw = $this->renderer->render( $raw );
		$raw = preg_replace( '/<br\\s*\\/?>/i', "\n", $raw );
		$raw = is_string( $raw ) ? wp_strip_all_tags( $raw ) : '';
		$raw = html_entity_decode( $raw );
		$raw = str_replace( "\r\n", "\n", $raw );

		$lines = [];
		foreach ( explode( "\n", $raw ) as $line ) {
			$line = trim( $line );
			if ( '' !== $line ) {
				$lines[] = $line;
			}
		}

		if ( count( $lines ) > $max_lines ) {
			$lines     = array_slice( $lines, -$max_lines );
			$truncated = true;
		}

		return $lines;
	}

	/**
	 * Resolve the actual file path for a log excerpt.
	 *
	 * @param string $path Log file path or basename.
	 *
	 * @return string|null
	 */
	private function resolve_path( string $path ): ?string {
		if ( '' === $path ) {
			return null;
		}

		if ( is_readable( $path ) ) {
			return $this->maybe_wrap_compressed_path( $path );
		}

		$log_folder = get_site_option( 'backwpup_cfg_logfolder' );
		$log_folder = BackWPup_File::get_absolute_path( (string) $log_folder );
		$log_folder = untrailingslashit( $log_folder );
		$filename   = basename( $path );
		$base       = $log_folder . '/' . $filename;

		if ( is_readable( $base ) ) {
			return $this->maybe_wrap_compressed_path( $base );
		}

		if ( substr( $base, -5 ) !== '.html' && is_readable( $base . '.html' ) ) {
			return $base . '.html';
		}

		if ( substr( $base, -8 ) !== '.html.gz' && is_readable( $base . '.html.gz' ) ) {
			return 'compress.zlib://' . $base . '.html.gz';
		}

		return null;
	}

	/**
	 * Return a stream wrapper for compressed logs.
	 *
	 * @param string $path Resolved filesystem path.
	 *
	 * @return string
	 */
	private function maybe_wrap_compressed_path( string $path ): string {
		if ( '.gz' === substr( $path, -3 ) ) {
			return 'compress.zlib://' . $path;
		}

		return $path;
	}
}
