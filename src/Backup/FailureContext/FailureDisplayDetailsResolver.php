<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Backup\FailureContext;

class FailureDisplayDetailsResolver {

	/**
	 * Display details providers.
	 *
	 * @var FailureDisplayDetailsProviderInterface[]
	 */
	private array $providers;

	/**
	 * Constructor.
	 *
	 * @param FailureDisplayDetailsProviderInterface ...$providers Display details providers.
	 */
	public function __construct( FailureDisplayDetailsProviderInterface ...$providers ) {
		$this->providers = $providers;
	}

	/**
	 * Resolve display details for a failure reason.
	 *
	 * @param string $reason_code Failure reason code.
	 * @param string $destination Destination identifier.
	 * @return array
	 */
	public function resolve( string $reason_code, string $destination = '' ): array {
		$reason_code = strtolower( trim( $reason_code ) );
		$destination = strtoupper( trim( $destination ) );

		if ( '' === $reason_code ) {
			return [];
		}

		foreach ( $this->providers as $provider ) {
			if ( $provider->supports( $destination ) ) {
				$details = $provider->get_details( $reason_code, $destination );

				if ( [] !== $details ) {
					return $details;
				}
			}
		}

		return [];
	}
}
