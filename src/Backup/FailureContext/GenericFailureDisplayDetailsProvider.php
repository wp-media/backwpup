<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Backup\FailureContext;

class GenericFailureDisplayDetailsProvider extends AbstractFailureDisplayDetailsProvider {

	/**
	 * Generic provider supports any destination as a fallback.
	 *
	 * @param string $destination Destination identifier.
	 * @return bool
	 */
	public function supports( string $destination ): bool {
		return '' !== trim( $destination );
	}
}
