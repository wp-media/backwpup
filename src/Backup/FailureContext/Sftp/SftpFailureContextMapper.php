<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Backup\FailureContext\Sftp;

use WPMedia\BackWPup\Backup\ReasonCode;

class SftpFailureContextMapper {

	/**
	 * Map an SFTP error message to a normalized failure context.
	 *
	 * @param string $message SFTP error message.
	 * @return array
	 */
	public function map( string $message ): array {
		$normalized = strtolower( $message );

		if (
			false !== strpos( $normalized, 'login failed' )
			|| false !== strpos( $normalized, 'not authenticated' )
			|| false !== strpos( $normalized, 'authentication failed' )
		) {
			return [
				'reason_code'   => ReasonCode::REASON_INCORRECT_LOGIN,
				'destination'   => 'SFTP',
				'provider_code' => 'login_failed',
			];
		}

		if (
			false !== strpos( $normalized, 'no space left' )
			|| false !== strpos( $normalized, 'quota exceeded' )
			|| false !== strpos( $normalized, 'quota' )
			|| false !== strpos( $normalized, 'not enough space' )
		) {
			return [
				'reason_code'   => ReasonCode::REASON_NOT_ENOUGH_STORAGE,
				'destination'   => 'SFTP',
				'provider_code' => 'insufficient_storage',
			];
		}

		if (
			false !== strpos( $normalized, 'no such file' )
			|| false !== strpos( $normalized, 'no such path' )
			|| false !== strpos( $normalized, 'path not found' )
			|| false !== strpos( $normalized, 'directory not found' )
		) {
			return [
				'reason_code'   => ReasonCode::REASON_NOT_FOUND,
				'destination'   => 'SFTP',
				'provider_code' => 'path_not_found',
			];
		}

		if (
			false !== strpos( $normalized, 'cannot connect because' )
			|| false !== strpos( $normalized, 'connection lost' )
			|| false !== strpos( $normalized, 'connection closed' )
			|| false !== strpos( $normalized, 'connection timed out' )
			|| false !== strpos( $normalized, 'network is unreachable' )
			|| false !== strpos( $normalized, 'no route to host' )
		) {
			return [
				'reason_code'   => ReasonCode::REASON_SERVICE_UNAVAILABLE,
				'destination'   => 'SFTP',
				'provider_code' => 'connection_failed',
			];
		}

		if (
			false !== strpos( $normalized, 'permission denied' )
			|| false !== strpos( $normalized, 'access denied' )
			|| false !== strpos( $normalized, 'cannot write to remote file' )
			|| false !== strpos( $normalized, 'not writable' )
		) {
			return [
				'reason_code'   => ReasonCode::REASON_INSUFFICIENT_PERMISSIONS,
				'destination'   => 'SFTP',
				'provider_code' => 'permission_denied',
			];
		}

		return [];
	}
}
