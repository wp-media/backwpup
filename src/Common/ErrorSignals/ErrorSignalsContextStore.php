<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Common\ErrorSignals;

class ErrorSignalsContextStore {
	private const OPTION_KEY = 'backwpup_recent_error_contexts';
	private const MAX_ITEMS  = 20;

	/**
	 * Store context data from a job error signal.
	 *
	 * @param array $signal Signal payload.
	 * @return void
	 */
	public function store( array $signal ): void {
		$job_id = (int) ( $signal['job_id'] ?? 0 );
		if ( $job_id <= 0 ) {
			return;
		}

		$context = $signal['context'] ?? null;
		if ( ! is_array( $context ) || empty( $context ) ) {
			return;
		}

		$context = $this->sanitize_context( $context );
		if ( empty( $context['reason_code'] ) ) {
			return;
		}

		$item = [
			'timestamp' => (int) ( $signal['timestamp'] ?? time() ),
			'job_id'    => $job_id,
			'context'   => $context,
		];

		$signals = (array) get_site_option( self::OPTION_KEY, [] );
		array_unshift( $signals, $item );

		$signals = $this->dedupe( $signals );
		$signals = array_slice( $signals, 0, self::MAX_ITEMS );

		update_site_option( self::OPTION_KEY, $signals );
	}

	/**
	 * Get latest context for a job.
	 *
	 * @param int $job_id Job ID.
	 * @param int $min_timestamp Min timestamp to accept.
	 * @return array
	 */
	public function latest_for_job( int $job_id, int $min_timestamp = 0 ): array {
		if ( $job_id <= 0 ) {
			return [];
		}

		$signals = (array) get_site_option( self::OPTION_KEY, [] );
		foreach ( $signals as $signal ) {
			if ( ! is_array( $signal ) ) {
				continue;
			}

			if ( (int) ( $signal['job_id'] ?? 0 ) !== $job_id ) {
				continue;
			}

			$timestamp = (int) ( $signal['timestamp'] ?? 0 );
			if ( $min_timestamp > 0 && $timestamp < $min_timestamp ) {
				continue;
			}

			return is_array( $signal ) ? $signal : [];
		}

		return [];
	}

	/**
	 * Get latest context for a job and destination.
	 *
	 * @param int    $job_id Job ID.
	 * @param string $destination Destination identifier.
	 * @param int    $min_timestamp Min timestamp to accept.
	 * @return array
	 */
	public function latest_for_job_destination( int $job_id, string $destination, int $min_timestamp = 0 ): array {
		if ( $job_id <= 0 ) {
			return [];
		}

		$destination = strtoupper( trim( $destination ) );
		if ( '' === $destination ) {
			return [];
		}

		$signals = (array) get_site_option( self::OPTION_KEY, [] );
		foreach ( $signals as $signal ) {
			if ( ! is_array( $signal ) ) {
				continue;
			}

			if ( (int) ( $signal['job_id'] ?? 0 ) !== $job_id ) {
				continue;
			}

			$timestamp = (int) ( $signal['timestamp'] ?? 0 );
			if ( $min_timestamp > 0 && $timestamp < $min_timestamp ) {
				continue;
			}

			$context             = is_array( $signal['context'] ?? null ) ? $signal['context'] : [];
			$context_destination = strtoupper( (string) ( $context['destination'] ?? '' ) );
			if ( '' === $context_destination || $context_destination !== $destination ) {
				continue;
			}

			return $signal;
		}

		return [];
	}

	/**
	 * Sanitize context values.
	 *
	 * @param array $context Context values.
	 * @return array
	 */
	private function sanitize_context( array $context ): array {
		$allowed = [
			'reason_code',
			'destination',
			'provider_code',
			'http_status',
		];

		$clean = [];
		foreach ( $allowed as $key ) {
			if ( ! array_key_exists( $key, $context ) ) {
				continue;
			}

			$value = $context[ $key ];
			if ( null === $value ) {
				continue;
			}

			if ( 'http_status' === $key ) {
				$clean[ $key ] = (int) $value;
				continue;
			}

			if ( is_scalar( $value ) ) {
				$clean[ $key ] = trim( (string) $value );
			}
		}

		if ( isset( $clean['reason_code'] ) ) {
			$clean['reason_code'] = strtolower( $clean['reason_code'] );
		}

		return $clean;
	}

	/**
	 * Remove consecutive duplicates.
	 *
	 * @param array $signals Stored data.
	 * @return array
	 */
	private function dedupe( array $signals ): array {
		$out       = [];
		$last_hash = null;

		foreach ( $signals as $signal ) {
			if ( ! is_array( $signal ) ) {
				continue;
			}

			$context = is_array( $signal['context'] ?? null ) ? $signal['context'] : [];
			$hash    = md5(
				( $signal['job_id'] ?? '' )
				. '|' . ( $context['reason_code'] ?? '' )
				. '|' . ( $context['destination'] ?? '' )
				. '|' . ( $context['provider_code'] ?? '' )
				. '|' . ( $context['http_status'] ?? '' )
			);

			if ( $hash === $last_hash ) {
				continue;
			}

			$out[]     = $signal;
			$last_hash = $hash;
		}

		return $out;
	}
}
