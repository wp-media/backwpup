<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Backup\FailureContext;

use WPMedia\BackWPup\Backup\FailureReasonResolver;

abstract class AbstractFailureDisplayDetailsProvider implements FailureDisplayDetailsProviderInterface {

	/**
	 * Return the details map keyed by failure reason code.
	 *
	 * @param string $destination Destination identifier.
	 * @return array
	 */
	protected function details_map( string $destination ): array {
		$destination = $this->destination_label( $destination );

		return [
			FailureReasonResolver::REASON_INCORRECT_LOGIN  => $this->incorrect_login_details( $destination ),
			FailureReasonResolver::REASON_NOT_ENOUGH_STORAGE => $this->not_enough_storage_details( $destination ),
			FailureReasonResolver::REASON_INSUFFICIENT_PERMISSIONS => $this->insufficient_permissions_details( $destination ),
			FailureReasonResolver::REASON_NOT_FOUND        => $this->not_found_details( $destination ),
			FailureReasonResolver::REASON_CONFLICT         => $this->conflict_details( $destination ),
			FailureReasonResolver::REASON_INVALID_REQUEST  => $this->invalid_request_details( $destination ),
			FailureReasonResolver::REASON_INVALID_PATH_OR_NAME => $this->invalid_path_or_name_details( $destination ),
			FailureReasonResolver::REASON_FILE_TOO_LARGE   => $this->file_too_large_details( $destination ),
			FailureReasonResolver::REASON_RATE_LIMITED     => $this->rate_limited_details( $destination ),
			FailureReasonResolver::REASON_QUOTA_EXCEEDED   => $this->quota_exceeded_details( $destination ),
			FailureReasonResolver::REASON_SERVICE_UNAVAILABLE => $this->service_unavailable_details( $destination ),
			FailureReasonResolver::REASON_TIMEOUT_OR_NETWORK => $this->timeout_or_network_details( $destination ),
			FailureReasonResolver::REASON_ACCOUNT_DISABLED_OR_SUSPENDED => $this->account_disabled_or_suspended_details( $destination ),
			FailureReasonResolver::REASON_MALWARE_DETECTED => $this->malware_detected_details( $destination ),
			FailureReasonResolver::REASON_UNKNOWN_ERROR    => $this->unknown_error_details( $destination ),
		];
	}

	/**
	 * Return display details for authentication failures.
	 *
	 * @param string $destination Normalized destination label.
	 * @return array
	 */
	protected function incorrect_login_details( string $destination ): array {
		return [
			'label'     => __( 'incorrect login', 'backwpup' ),
			'summary'   => $this->format(
				/* translators: %s: storage destination name, e.g. Dropbox, Google Drive, FTP, SFTP. */
				__( '%s authentication failed (credentials rejected).', 'backwpup' ),
				$destination
			),
			'next_step' => $this->format(
				/* translators: %s: storage destination name, e.g. Dropbox, Google Drive, FTP, SFTP. */
				__( 'Check the connection settings for %s, reconnect it if needed, and rerun the backup.', 'backwpup' ),
				$destination
			),
		];
	}

	/**
	 * Return display details for storage capacity failures.
	 *
	 * @param string $destination Normalized destination label.
	 * @return array
	 */
	protected function not_enough_storage_details( string $destination ): array {
		return [
			'label'     => __( 'not enough storage', 'backwpup' ),
			'summary'   => $this->format(
				/* translators: %s: storage destination name, e.g. Dropbox, Google Drive, FTP, SFTP. */
				__( '%s does not have enough free space for this backup.', 'backwpup' ),
				$destination
			),
			'next_step' => $this->format(
				/* translators: %s: storage destination name, e.g. Dropbox, Google Drive, FTP, SFTP. */
				__( 'Free up space or increase the storage quota for %s, then rerun the backup.', 'backwpup' ),
				$destination
			),
		];
	}

	/**
	 * Return display details for insufficient permissions.
	 *
	 * @param string $destination Normalized destination label.
	 * @return array
	 */
	protected function insufficient_permissions_details( string $destination ): array {
		return [
			'label'     => __( 'permission denied', 'backwpup' ),
			'summary'   => $this->format(
				/* translators: %s: storage destination name, e.g. Dropbox, Google Drive, FTP, SFTP. */
				__( '%s rejected the backup because permissions are insufficient.', 'backwpup' ),
				$destination
			),
			'next_step' => $this->format(
				/* translators: %s: storage destination name, e.g. Dropbox, Google Drive, FTP, SFTP. */
				__( 'Check that the connected account can create folders and upload files to %s, then rerun the backup.', 'backwpup' ),
				$destination
			),
		];
	}

	/**
	 * Return display details for missing path or file failures.
	 *
	 * @param string $destination Normalized destination label.
	 * @return array
	 */
	protected function not_found_details( string $destination ): array {
		return [
			'label'     => __( 'path not found', 'backwpup' ),
			'summary'   => $this->format(
				/* translators: %s: storage destination name, e.g. Dropbox, Google Drive, FTP, SFTP. */
				__( 'The target file or folder could not be found on %s.', 'backwpup' ),
				$destination
			),
			'next_step' => $this->format(
				/* translators: %s: storage destination name, e.g. Dropbox, Google Drive, FTP, SFTP. */
				__( 'Check the configured destination path or folder for %s and rerun the backup.', 'backwpup' ),
				$destination
			),
		];
	}

	/**
	 * Return display details for destination conflicts.
	 *
	 * @param string $destination Normalized destination label.
	 * @return array
	 */
	protected function conflict_details( string $destination ): array {
		return [
			'label'     => __( 'conflict', 'backwpup' ),
			'summary'   => $this->format(
				/* translators: %s: storage destination name, e.g. Dropbox, Google Drive, FTP, SFTP. */
				__( '%s rejected the backup because the target resource is in conflict.', 'backwpup' ),
				$destination
			),
			'next_step' => $this->format(
				/* translators: %s: storage destination name, e.g. Dropbox, Google Drive, FTP, SFTP. */
				__( 'Review the destination path or filename for %s and rerun the backup.', 'backwpup' ),
				$destination
			),
		];
	}

	/**
	 * Return display details for invalid requests.
	 *
	 * @param string $destination Normalized destination label.
	 * @return array
	 */
	protected function invalid_request_details( string $destination ): array {
		return [
			'label'     => __( 'invalid request', 'backwpup' ),
			'summary'   => $this->format(
				/* translators: %s: storage destination name, e.g. Dropbox, Google Drive, FTP, SFTP. */
				__( '%s rejected the backup request as invalid.', 'backwpup' ),
				$destination
			),
			'next_step' => $this->format(
				/* translators: %s: storage destination name, e.g. Dropbox, Google Drive, FTP, SFTP. */
				__( 'Check the destination configuration for %s and rerun the backup.', 'backwpup' ),
				$destination
			),
		];
	}

	/**
	 * Return display details for invalid path or file name failures.
	 *
	 * @param string $destination Normalized destination label.
	 * @return array
	 */
	protected function invalid_path_or_name_details( string $destination ): array {
		return [
			'label'     => __( 'invalid path or name', 'backwpup' ),
			'summary'   => $this->format(
				/* translators: %s: storage destination name, e.g. Dropbox, Google Drive, FTP, SFTP. */
				__( '%s rejected the backup because the path or filename is invalid.', 'backwpup' ),
				$destination
			),
			'next_step' => $this->format(
				/* translators: %s: storage destination name, e.g. Dropbox, Google Drive, FTP, SFTP. */
				__( 'Check the configured path and naming rules for %s, then rerun the backup.', 'backwpup' ),
				$destination
			),
		];
	}

	/**
	 * Return display details for file size limit failures.
	 *
	 * @param string $destination Normalized destination label.
	 * @return array
	 */
	protected function file_too_large_details( string $destination ): array {
		return [
			'label'     => __( 'file too large', 'backwpup' ),
			'summary'   => $this->format(
				/* translators: %s: storage destination name, e.g. Dropbox, Google Drive, FTP, SFTP. */
				__( '%s rejected the backup because the file is too large.', 'backwpup' ),
				$destination
			),
			'next_step' => $this->format(
				/* translators: %s: storage destination name, e.g. Dropbox, Google Drive, FTP, SFTP. */
				__( 'Reduce the backup size or verify the upload limits for %s, then rerun the backup.', 'backwpup' ),
				$destination
			),
		];
	}

	/**
	 * Return display details for rate limiting.
	 *
	 * @param string $destination Normalized destination label.
	 * @return array
	 */
	protected function rate_limited_details( string $destination ): array {
		return [
			'label'     => __( 'rate limited', 'backwpup' ),
			'summary'   => $this->format(
				/* translators: %s: storage destination name, e.g. Dropbox, Google Drive, FTP, SFTP. */
				__( '%s temporarily rejected the backup because too many requests were sent.', 'backwpup' ),
				$destination
			),
			'next_step' => __( 'Wait a moment and rerun the backup.', 'backwpup' ),
		];
	}

	/**
	 * Return display details for quota exceeded failures.
	 *
	 * @param string $destination Normalized destination label.
	 * @return array
	 */
	protected function quota_exceeded_details( string $destination ): array {
		return [
			'label'     => __( 'quota exceeded', 'backwpup' ),
			'summary'   => $this->format(
				/* translators: %s: storage destination name, e.g. Dropbox, Google Drive, FTP, SFTP. */
				__( '%s quota has been exceeded.', 'backwpup' ),
				$destination
			),
			'next_step' => $this->format(
				/* translators: %s: storage destination name, e.g. Dropbox, Google Drive, FTP, SFTP. */
				__( 'Increase the storage quota or free up space on %s, then rerun the backup.', 'backwpup' ),
				$destination
			),
		];
	}

	/**
	 * Return display details for unavailable services.
	 *
	 * @param string $destination Normalized destination label.
	 * @return array
	 */
	protected function service_unavailable_details( string $destination ): array {
		return [
			'label'     => __( 'service unavailable', 'backwpup' ),
			'summary'   => $this->format(
				/* translators: %s: storage destination name, e.g. Dropbox, Google Drive, FTP, SFTP. */
				__( '%s is temporarily unavailable.', 'backwpup' ),
				$destination
			),
			'next_step' => $this->format(
				/* translators: %s: storage destination name, e.g. Dropbox, Google Drive, FTP, SFTP. */
				__( 'Check the provider status or connection for %s and rerun the backup when the service is available.', 'backwpup' ),
				$destination
			),
		];
	}

	/**
	 * Return display details for timeout or network failures.
	 *
	 * @param string $destination Normalized destination label.
	 * @return array
	 */
	protected function timeout_or_network_details( string $destination ): array {
		return [
			'label'     => __( 'network or timeout error', 'backwpup' ),
			'summary'   => $this->format(
				/* translators: %s: storage destination name, e.g. Dropbox, Google Drive, FTP, SFTP. */
				__( 'The backup failed because the connection to %s timed out or was interrupted.', 'backwpup' ),
				$destination
			),
			'next_step' => $this->format(
				/* translators: %s: storage destination name, e.g. Dropbox, Google Drive, FTP, SFTP. */
				__( 'Check the network connection to %s and rerun the backup.', 'backwpup' ),
				$destination
			),
		];
	}

	/**
	 * Return display details for disabled or suspended accounts.
	 *
	 * @param string $destination Normalized destination label.
	 * @return array
	 */
	protected function account_disabled_or_suspended_details( string $destination ): array {
		return [
			'label'     => __( 'account disabled or suspended', 'backwpup' ),
			'summary'   => $this->format(
				/* translators: %s: storage destination name, e.g. Dropbox, Google Drive, FTP, SFTP. */
				__( '%s account is disabled or suspended.', 'backwpup' ),
				$destination
			),
			'next_step' => $this->format(
				/* translators: %s: storage destination name, e.g. Dropbox, Google Drive, FTP, SFTP. */
				__( 'Review the account status for %s with the provider and rerun the backup after access is restored.', 'backwpup' ),
				$destination
			),
		];
	}

	/**
	 * Return display details for malware detection failures.
	 *
	 * @param string $destination Normalized destination label.
	 * @return array
	 */
	protected function malware_detected_details( string $destination ): array {
		return [
			'label'     => __( 'malware detected', 'backwpup' ),
			'summary'   => $this->format(
				/* translators: %s: storage destination name, e.g. Dropbox, Google Drive, FTP, SFTP. */
				__( '%s rejected the backup because malware was detected.', 'backwpup' ),
				$destination
			),
			'next_step' => $this->format(
				/* translators: %s: storage destination name, e.g. Dropbox, Google Drive, FTP, SFTP. */
				__( 'Review the security warning from %s and resolve it before rerunning the backup.', 'backwpup' ),
				$destination
			),
		];
	}

	/**
	 * Return display details for unknown errors.
	 *
	 * @param string $destination Normalized destination label.
	 * @return array
	 */
	protected function unknown_error_details( string $destination ): array {
		return [
			'label'     => __( 'unknown error', 'backwpup' ),
			'summary'   => $this->format(
				/* translators: %s: storage destination name, e.g. Dropbox, Google Drive, FTP, SFTP. */
				__( 'The backup to %s failed with an unknown error.', 'backwpup' ),
				$destination
			),
			'next_step' => $this->format(
				/* translators: %s: storage destination name, e.g. Dropbox, Google Drive, FTP, SFTP. */
				__( 'Check the job log for details, verify your %s settings, and try again.', 'backwpup' ),
				$destination
			),
		];
	}


	/**
	 * Normalize the destination label used in copy.
	 *
	 * @param string $destination Destination identifier.
	 * @return string
	 */
	protected function destination_label( string $destination ): string {
		return strtoupper( trim( $destination ) );
	}

	/**
	 * Format a translated string.
	 *
	 * @param string $template Translated template.
	 * @param string ...$args Replacement values.
	 * @return string
	 */
	protected function format( string $template, string ...$args ): string {
		return sprintf( $template, ...$args );
	}

	/**
	 * Return display details for a failure reason.
	 *
	 * @param string $reason_code Failure reason code.
	 * @param string $destination Destination identifier.
	 * @return array
	 */
	public function get_details( string $reason_code, string $destination = '' ): array {
		$reason_code = strtolower( trim( $reason_code ) );
		$destination = strtoupper( trim( $destination ) );

		if ( '' === $reason_code ) {
			return [];
		}

		$details = $this->details_map( $destination );

		return $details[ $reason_code ] ?? [];
	}
}
