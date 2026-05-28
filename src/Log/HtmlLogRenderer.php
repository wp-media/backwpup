<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Log;

/**
 * Render log HTML for the site timezone.
 */
final class HtmlLogRenderer {

	/**
	 * Re-render timestamp spans using the site timezone and date formats.
	 *
	 * @param string $content Raw HTML log fragment.
	 *
	 * @return string
	 */
	public function render( string $content ): string {
		$date_format = (string) get_option( 'date_format' );
		$time_format = (string) get_option( 'time_format' );

		$result = preg_replace_callback(
			'|<span datetime="([^"]+)"([^>]*)>\[[^\]]*\]</span>|',
			static function ( array $matches ) use ( $date_format, $time_format ): string {
				$ts = strtotime( $matches[1] );
				if ( false === $ts || 0 >= $ts ) {
					return $matches[0];
				}

				$formatted = wp_date( $date_format . ' ' . $time_format, $ts );

				return '<span datetime="' . esc_attr( $matches[1] ) . '"' . $matches[2] . '>[' . esc_html( (string) $formatted ) . ']</span>';
			},
			$content
		);

		return $result ?? $content;
	}
}
