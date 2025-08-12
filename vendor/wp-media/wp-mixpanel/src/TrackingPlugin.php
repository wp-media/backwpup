<?php
declare(strict_types=1);

namespace WPMedia\Mixpanel;

class TrackingPlugin extends Tracking {
	/**
	 * Plugin name & version
	 *
	 * @var string
	 */
	private $plugin;

	/**
	 * Brand name
	 *
	 * @var string
	 */
	private $brand;

	/**
	 * Product name
	 *
	 * @var string
	 */
	private $product;

	/**
	 * Constructor
	 *
	 * @param string $mixpanel_token Mixpanel token.
	 * @param string $plugin         Plugin name.
	 * @param string $brand          Brand name.
	 * @param string $product        Product name.
	 */
	public function __construct( string $mixpanel_token, string $plugin, string $brand = '', string $product = '' ) {
		$options = [
			'consumer'  => 'wp',
			'consumers' => [
				'wp' => 'WPMedia\\Mixpanel\\WPConsumer',
			],
		];

		parent::__construct( $mixpanel_token, $options );

		$this->plugin  = $plugin;
		$this->brand   = $brand;
		$this->product = $product;
	}

	/**
	 * Track an event in Mixpanel with plugin context
	 *
	 * @param string  $event      Event name.
	 * @param mixed[] $properties Event properties.
	 */
	public function track( string $event, array $properties ): void {
		$host = wp_parse_url( get_home_url(), PHP_URL_HOST );

		if ( ! $host ) {
			$host = '';
		}

		$defaults = [
			'domain'      => $this->hash( $host ),
			'wp_version'  => $this->get_wp_version(),
			'php_version' => $this->get_php_version(),
			'plugin'      => $this->plugin,
			'brand'       => $this->brand,
			'product'     => $this->product,
		];

		$properties = array_merge( $properties, $defaults );

		parent::track( $event, $properties );
	}

	/**
	 * Track opt-in status change in Mixpanel
	 *
	 * @param bool $status Opt-in status.
	 *
	 * @return void
	 */
	public function track_optin( $status ): void {
		$this->track(
			'WordPress Plugin Data Consent Changed',
			[
				'opt_in_status' => $status,
			]
		);
	}
}
