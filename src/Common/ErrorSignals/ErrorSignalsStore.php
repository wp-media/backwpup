<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Common\ErrorSignals;

use BackWPup_Job;

class ErrorSignalsStore {

	private const OPTION_KEY = 'backwpup_recent_error_signals';
	private const MAX_ITEMS  = 20;

	/**
	 * Last records.
	 *
	 * @param int $limit Limit.
	 * @return array
	 */
	public function latest( int $limit = self::MAX_ITEMS ): array {
		$signals = (array) get_site_option( self::OPTION_KEY, [] );
		return array_slice( $signals, 0, $limit );
	}

	/**
	 * Store data.
	 *
	 * @param array $signal Stored data.
	 * @return void
	 */
	public function store( array $signal ): void {

		$msg = wp_strip_all_tags( (string) ( $signal['message'] ?? '' ) );
		$msg = mb_substr( $msg, 0, 500 );

		$item = [
			'level'     => (string) ( $signal['level'] ?? '' ),
			'type'      => (int) ( $signal['type'] ?? 0 ),
			'message'   => $msg,
			'timestamp' => (int) ( $signal['timestamp'] ?? time() ),
			'job_id'    => (int) ( $signal['job_id'] ?? 0 ),
			'job_name'  => (string) ( $signal['job_name'] ?? '' ),
			'step'      => (string) ( $signal['step'] ?? '' ),

		];

		if ( ! empty( $signal['file'] ) ) {
			$item['file']    = (string) ( $signal['file'] );
			$item['line']    = (int) ( $signal['line'] ?? 0 );
			$item['logfile'] = (string) ( $signal['logfile'] ?? '' );
		}

		$signals = (array) get_site_option( self::OPTION_KEY, [] );
		array_unshift( $signals, $item );

		$signals = $this->dedupe( $signals );
		$signals = array_slice( $signals, 0, self::MAX_ITEMS );

		update_site_option( self::OPTION_KEY, $signals );
	}

	/**
	 * Remove unnecessary duplicates.
	 *
	 * @param array $signals Stored data.
	 * @return array
	 */
	private function dedupe( array $signals ): array {
		$out       = [];
		$last_hash = null;

		foreach ( $signals as $s ) {
			$hash = md5( ( $s['level'] ?? '' ) . '|' . ( $s['type'] ?? '' ) . '|' . ( $s['step'] ?? '' ) . '|' . ( $s['message'] ?? '' ) );
			if ( $hash === $last_hash ) {
				continue;
			}
			$out[]     = $s;
			$last_hash = $hash;
		}

		return $out;
	}
}
