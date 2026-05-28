<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Backup\FailureContext\SugarSync;

use WPMedia\BackWPup\Backup\FailureContext\AbstractFailureDisplayDetailsProvider;

class SugarSyncFailureDisplayDetailsProvider extends AbstractFailureDisplayDetailsProvider {

	/**
	 * Destination identifier and label.
	 */
	private const DESTINATION = 'SugarSync';

	/**
	 * Whether the provider supports the given destination.
	 *
	 * @param string $destination Destination identifier.
	 * @return bool
	 */
	public function supports( string $destination ): bool {
		return strtoupper( self::DESTINATION ) === strtoupper( trim( $destination ) );
	}

	/**
	 * Normalize the destination label used in copy.
	 *
	 * @param string $destination Destination identifier.
	 * @return string
	 */
	protected function destination_label( string $destination ): string {
		return self::DESTINATION;
	}
}
