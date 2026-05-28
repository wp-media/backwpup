<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Backup\FailureContext\SugarSync;

use WPMedia\BackWPup\Backup\ReasonCode;

class SugarSyncFailureContextMapper {

	private const DESTINATION = 'SUGARSYNC';

	/**
	 * Four exception formats are produced by BackWPup_Destination_SugarSync_API_Exception:
	 *   - HTTP errors from wp_remote_request: message "SugarSync error: (STATUS) MESSAGE", code STATUS
	 *   - WP_Error transport failures:        message "SugarSync error: MESSAGE",           code 0
	 *   - cURL errors from the upload path:   message "cUrl Error: MESSAGE",                code 0
	 *   - HTTP errors from the upload path:   message "Http Error: STATUS",                 code STATUS
	 *
	 * @param \Exception $exception Exception instance.
	 * @return array
	 */
	public function map( \Exception $exception ): array {
		$normalized = strtolower( $exception->getMessage() );
		$status     = (string) $exception->getCode();

		if (
			'401' === $status
			|| false !== strpos( $normalized, 'auth' )
		) {
			return [
				'reason_code'   => ReasonCode::REASON_INCORRECT_LOGIN,
				'destination'   => self::DESTINATION,
				'provider_code' => $status ?: 'auth_failed',
			];
		}

		if ( '403' === $status ) {
			return [
				'reason_code'   => ReasonCode::REASON_INSUFFICIENT_PERMISSIONS,
				'destination'   => self::DESTINATION,
				'provider_code' => $status,
			];
		}

		if (
			'507' === $status
			|| false !== strpos( $normalized, 'quota' )
			|| false !== strpos( $normalized, 'insufficient' )
			|| false !== strpos( $normalized, 'not enough' )
		) {
			return [
				'reason_code'   => ReasonCode::REASON_NOT_ENOUGH_STORAGE,
				'destination'   => self::DESTINATION,
				'provider_code' => $status ?: 'quota_exceeded',
			];
		}

		if (
			( (int) $status >= 500 )
			|| false !== strpos( $normalized, 'timed out' )
			|| false !== strpos( $normalized, 'could not resolve host' )
			|| false !== strpos( $normalized, 'connection refused' )
			|| false !== strpos( $normalized, 'failed to connect' )
			|| false !== strpos( $normalized, 'network is unreachable' )
			|| false !== strpos( $normalized, 'ssl' )
		) {
			return [
				'reason_code'   => ReasonCode::REASON_TIMEOUT_OR_NETWORK,
				'destination'   => self::DESTINATION,
				'provider_code' => $status ?: 'network_error',
			];
		}

		return [];
	}
}
