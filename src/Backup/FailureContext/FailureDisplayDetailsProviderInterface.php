<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Backup\FailureContext;

interface FailureDisplayDetailsProviderInterface {

	/**
	 * Whether the provider supports the given destination.
	 *
	 * @param string $destination Destination identifier.
	 * @return bool
	 */
	public function supports( string $destination ): bool;

	/**
	 * Return display details for a failure reason.
	 *
	 * @param string $reason_code Failure reason code.
	 * @param string $destination Destination identifier.
	 * @return array
	 */
	public function get_details( string $reason_code, string $destination = '' ): array;
}
