<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Backup\FailureContext\Dropbox;

use WPMedia\BackWPup\Backup\ReasonCode;

class DropboxFailureContextMapper {

	/**
	 * Destination identifier.
	 */
	private const DESTINATION = 'DROPBOX';

	/**
	 * Map Dropbox error details to a normalized failure context.
	 *
	 * @param array $error_data Error details from the API.
	 * @param int   $status     HTTP status code.
	 * @return array
	 */
	public function map_api_error( array $error_data, int $status ): array {
		$context = [
			'destination' => self::DESTINATION,
			'http_status' => $status,
		];

		$tag         = $this->provider_code( $error_data );
		$reason_code = $this->reason_code_from_tag( $tag );
		if ( '' !== $tag ) {
			$context['provider_code'] = $tag;
		}

		if ( 401 === $status ) {
			$context['reason_code'] = ReasonCode::REASON_INCORRECT_LOGIN;

			return $context;
		}

		if ( 403 === $status ) {
			$context['reason_code'] = ReasonCode::REASON_INSUFFICIENT_PERMISSIONS;

			return $context;
		}

		if ( 409 === $status ) {
			if ( '' !== $reason_code ) {
				$context['reason_code'] = $reason_code;
			}

			return $context;
		}

		if ( 429 === $status ) {
			$context['reason_code'] = ReasonCode::REASON_RATE_LIMITED;

			return $context;
		}

		if ( 400 === $status ) {
			$context['reason_code'] = ReasonCode::REASON_INVALID_REQUEST;

			return $context;
		}

		if ( $status >= 500 ) {
			$context['reason_code'] = ReasonCode::REASON_SERVICE_UNAVAILABLE;

			return $context;
		}

		if ( '' !== $reason_code ) {
			$context['reason_code'] = $reason_code;
		}

		return $context;
	}

	/**
	 * Resolve the most specific Dropbox provider code available.
	 *
	 * @param array $error_data Error details from the API.
	 * @return string
	 */
	private function provider_code( array $error_data ): string {
		$tag = (string) ( $error_data['.tag'] ?? '' );

		if (
			'' === $tag
			&& ! empty( $error_data['reason']['.tag'] )
			&& is_string( $error_data['reason']['.tag'] )
		) {
			return $error_data['reason']['.tag'];
		}

		if (
			'reason' === $tag
			&& ! empty( $error_data['reason']['.tag'] )
			&& is_string( $error_data['reason']['.tag'] )
		) {
			return $error_data['reason']['.tag'];
		}

		if (
			'path' === $tag
			&& ! empty( $error_data['reason']['.tag'] )
			&& is_string( $error_data['reason']['.tag'] )
		) {
			return $error_data['reason']['.tag'];
		}

		if (
			'path' === $tag
			&& ! empty( $error_data['path']['reason']['.tag'] )
			&& is_string( $error_data['path']['reason']['.tag'] )
		) {
			return $error_data['path']['reason']['.tag'];
		}

		if (
			'path' === $tag
			&& ! empty( $error_data['path']['.tag'] )
			&& is_string( $error_data['path']['.tag'] )
		) {
			return $error_data['path']['.tag'];
		}

		if (
			'lookup_failed' === $tag
			&& ! empty( $error_data['lookup_failed']['.tag'] )
			&& is_string( $error_data['lookup_failed']['.tag'] )
		) {
			return $error_data['lookup_failed']['.tag'];
		}

		return $tag;
	}

	/**
	 * Map Dropbox-specific tags to normalized reason codes.
	 *
	 * @param string $tag Dropbox provider code.
	 * @return string
	 */
	private function reason_code_from_tag( string $tag ): string {
		switch ( $tag ) {
			case 'insufficient_space':
				return ReasonCode::REASON_NOT_ENOUGH_STORAGE;

			case 'no_write_permission':
			case 'team_folder':
				return ReasonCode::REASON_INSUFFICIENT_PERMISSIONS;

			case 'malformed_path':
			case 'disallowed_name':
				return ReasonCode::REASON_INVALID_PATH_OR_NAME;

			case 'not_found':
				return ReasonCode::REASON_NOT_FOUND;

			case 'conflict':
				return ReasonCode::REASON_CONFLICT;

			default:
				return '';
		}
	}

	/**
	 * Map a specific reason code to a full context.
	 *
	 * @param string $reason_code Normalized reason code.
	 * @return array
	 */
	public function map_reason( string $reason_code ): array {
		return [
			'reason_code' => $reason_code,
			'destination' => self::DESTINATION,
		];
	}
}
