<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Log;

/**
 * Format UTC timestamps with WordPress date settings.
 */
final class WpDateLogFormatter {

	/**
	 * Format a UTC timestamp using the site date format.
	 *
	 * @param int $timestamp UTC timestamp.
	 *
	 * @return string
	 */
	public function format_date( int $timestamp ): string {
		return (string) wp_date( get_option( 'date_format' ), $timestamp );
	}

	/**
	 * Format a UTC timestamp using the site time format.
	 *
	 * @param int $timestamp UTC timestamp.
	 *
	 * @return string
	 */
	public function format_time( int $timestamp ): string {
		return (string) wp_date( get_option( 'time_format' ), $timestamp );
	}

	/**
	 * Format a UTC timestamp using the combined site date/time label.
	 *
	 * @param int $timestamp UTC timestamp.
	 *
	 * @return string
	 */
	public function format_datetime( int $timestamp ): string {
		return sprintf(
			/* translators: %1$s: date, %2$s: time. */
			__( '%1$s at %2$s', 'backwpup' ),
			$this->format_date( $timestamp ),
			$this->format_time( $timestamp )
		);
	}
}
