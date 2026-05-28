<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Backup\FailureContext\Ftp;

use WPMedia\BackWPup\Backup\ReasonCode;

class FtpFailureContextMapper {

	/**
	 * Map an FTP error message to a normalized failure context.
	 *
	 * @param string $message FTP error message.
	 * @return array
	 */
	public function map( string $message ): array {
		$normalized = strtolower( $message );
		$code       = '';

		if ( preg_match( '/\b(4|5)\d{2}\b/', $message, $matches ) ) {
			$code = $matches[0];
		}

		if (
			'530' === $code
			|| false !== strpos( $normalized, 'login' )
			|| false !== strpos( $normalized, 'not logged' )
			|| false !== strpos( $normalized, 'authentication' )
		) {
			return [
				'reason_code'   => ReasonCode::REASON_INCORRECT_LOGIN,
				'destination'   => 'FTP',
				'provider_code' => $code ?: 'login_failed',
			];
		}

		if (
			'552' === $code
			|| '452' === $code
			|| false !== strpos( $normalized, 'quota' )
			|| false !== strpos( $normalized, 'disk full' )
			|| false !== strpos( $normalized, 'no space' )
			|| false !== strpos( $normalized, 'insufficient' )
		) {
			return [
				'reason_code'   => ReasonCode::REASON_NOT_ENOUGH_STORAGE,
				'destination'   => 'FTP',
				'provider_code' => $code ?: 'insufficient_storage',
			];
		}

		if (
			'450' === $code
			|| (
				'550' === $code
				&& (
					false !== strpos( $normalized, 'no such file' )
					|| false !== strpos( $normalized, 'not found' )
					|| false !== strpos( $normalized, 'file unavailable' )
					|| false !== strpos( $normalized, 'directory unavailable' )
					|| false !== strpos( $normalized, 'path not found' )
				)
			)
			|| false !== strpos( $normalized, 'no such file' )
			|| false !== strpos( $normalized, 'directory not found' )
			|| false !== strpos( $normalized, 'path not found' )
		) {
			return [
				'reason_code'   => ReasonCode::REASON_NOT_FOUND,
				'destination'   => 'FTP',
				'provider_code' => $code ?: 'path_not_found',
			];
		}

		if (
			'421' === $code
			|| false !== strpos( $normalized, 'cannot connect' )
			|| false !== strpos( $normalized, 'connection timed out' )
			|| false !== strpos( $normalized, 'connection refused' )
			|| false !== strpos( $normalized, 'network is unreachable' )
			|| false !== strpos( $normalized, 'no route to host' )
			|| false !== strpos( $normalized, 'temporary failure in name resolution' )
		) {
			return [
				'reason_code'   => ReasonCode::REASON_SERVICE_UNAVAILABLE,
				'destination'   => 'FTP',
				'provider_code' => $code ?: 'connection_failed',
			];
		}

		if (
			'550' === $code
			|| false !== strpos( $normalized, 'permission denied' )
			|| false !== strpos( $normalized, 'access denied' )
			|| false !== strpos( $normalized, 'not writable' )
			|| false !== strpos( $normalized, 'cannot be created' )
			|| false !== strpos( $normalized, 'could not create file' )
		) {
			return [
				'reason_code'   => ReasonCode::REASON_INSUFFICIENT_PERMISSIONS,
				'destination'   => 'FTP',
				'provider_code' => $code ?: 'permission_denied',
			];
		}

		return [];
	}
}
