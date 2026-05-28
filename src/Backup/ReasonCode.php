<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Backup;

final class ReasonCode {
	public const REASON_NOT_ENOUGH_STORAGE            = 'not_enough_storage';
	public const REASON_INCORRECT_LOGIN               = 'incorrect_login';
	public const REASON_NOT_FOUND                     = 'not_found';
	public const REASON_CONFLICT                      = 'conflict';
	public const REASON_INVALID_REQUEST               = 'invalid_request';
	public const REASON_INVALID_PATH_OR_NAME          = 'invalid_path_or_name';
	public const REASON_FILE_TOO_LARGE                = 'file_too_large';
	public const REASON_RATE_LIMITED                  = 'rate_limited';
	public const REASON_QUOTA_EXCEEDED                = 'quota_exceeded';
	public const REASON_SERVICE_UNAVAILABLE           = 'service_unavailable';
	public const REASON_TIMEOUT_OR_NETWORK            = 'timeout_or_network';
	public const REASON_ACCOUNT_DISABLED_OR_SUSPENDED = 'account_disabled_or_suspended';
	public const REASON_MALWARE_DETECTED              = 'malware_detected';
	public const REASON_INSUFFICIENT_PERMISSIONS      = 'insufficient_permissions';
	public const REASON_UNKNOWN_ERROR                 = 'unknown_error';
	public const REASON_USER_ABORTED                  = 'user_aborted';

	/**
	 * Prevent instantiation.
	 */
	private function __construct() {
	}
}
