<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Backup;

use WPMedia\BackWPup\Common\ErrorSignals\ErrorSignalsContextStore;

class FailureReasonResolver {
	public const REASON_NOT_ENOUGH_STORAGE = 'not_enough_storage';
	public const REASON_INCORRECT_LOGIN    = 'incorrect_login';

	/**
	 * Error signals context store.
	 *
	 * @var ErrorSignalsContextStore
	 */
	private ErrorSignalsContextStore $context_store;

	/**
	 * Constructor.
	 *
	 * @param ErrorSignalsContextStore $context_store Context store instance.
	 */
	public function __construct( ErrorSignalsContextStore $context_store ) {
		$this->context_store = $context_store;
	}

	/**
	 * Resolve failure details for a job.
	 *
	 * @param int    $job_id Job ID.
	 * @param int    $min_timestamp Minimum accepted timestamp.
	 * @param array  $signal Latest error signal payload.
	 * @param string $destination Destination identifier.
	 * @return array
	 */
	public function resolve( int $job_id, int $min_timestamp = 0, array $signal = [], string $destination = '' ): array {
		if ( $job_id <= 0 ) {
			return [];
		}

		$context_signal = '' !== $destination
			? $this->context_store->latest_for_job_destination( $job_id, $destination, $min_timestamp )
			: $this->context_store->latest_for_job( $job_id, $min_timestamp );
		$context        = is_array( $context_signal['context'] ?? null ) ? $context_signal['context'] : [];
		$reason_code    = '';

		if ( ! empty( $context['reason_code'] ) ) {
			$reason_code = strtolower( (string) $context['reason_code'] );
		}

		if ( '' === $reason_code && ! empty( $signal['message'] ) ) {
			$reason_code = $this->reason_code_from_message( (string) $signal['message'] );
		}

		if ( '' === $reason_code ) {
			return [];
		}

		$message = $this->reason_message( $reason_code );

		if ( '' === $message ) {
			return [];
		}

		return [
			'error_code'    => $reason_code,
			'error_message' => $message,
		];
	}

	/**
	 * Map reason codes to UI messages.
	 *
	 * @param string $reason_code Reason code.
	 * @return string
	 */
	private function reason_message( string $reason_code ): string {
		switch ( $reason_code ) {
			case self::REASON_NOT_ENOUGH_STORAGE:
				return __( 'not enough storage', 'backwpup' );
			case self::REASON_INCORRECT_LOGIN:
				return __( 'incorrect login', 'backwpup' );
			default:
				return '';
		}
	}

	/**
	 * Infer a reason code from a signal message.
	 *
	 * @param string $message Signal message.
	 * @return string
	 */
	private function reason_code_from_message( string $message ): string {
		$normalized = strtolower( trim( $message ) );
		$normalized = preg_replace( '/^(error|warning|recoverable error|deprecated|strict notice):\s*/i', '', $normalized );
		$normalized = $normalized ? $normalized : strtolower( trim( $message ) );

		$login_patterns = [
			'not authenticated',
			'authentication failed',
			'invalidauthenticationtoken',
			'login failed',
			'invalid access key',
			'signature does not match',
			'invalid credentials',
			'unauthorized',
			'invalid token',
			'expired token',
			'invalid_grant',
			'no access token',
			'no refresh token',
			'authentication request failed',
		];

		foreach ( $login_patterns as $pattern ) {
			if ( false !== strpos( $normalized, $pattern ) ) {
				return self::REASON_INCORRECT_LOGIN;
			}
		}

		$storage_patterns = [
			'not enough space',
			'not enough storage',
			'no space left on device',
			'insufficient space',
			'insufficient storage',
			'insufficientstorage',
			'disk full',
			'storagequotaexceeded',
			'quotalimitreached',
			'quotaexceeded',
			'quota limit',
			'quota exceeded',
			'insufficient_space',
			'quotareached',
		];

		foreach ( $storage_patterns as $pattern ) {
			if ( false !== strpos( $normalized, $pattern ) ) {
				return self::REASON_NOT_ENOUGH_STORAGE;
			}
		}

		return '';
	}
}
