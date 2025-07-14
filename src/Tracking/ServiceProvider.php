<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Tracking;

use BackWPup;
use WPMedia\BackWPup\Dependencies\League\Container\ServiceProvider\AbstractServiceProvider;
use WPMedia\Mixpanel\Optin;
use WPMedia\Mixpanel\TrackingPlugin;

class ServiceProvider extends AbstractServiceProvider {
	/**
	 * Services provided by this provider
	 *
	 * @var array
	 */
	protected $provides = [
		Optin::class,
		TrackingPlugin::class,
	];

	/**
	 * Subscribers provided by this provider
	 *
	 * @var array
	 */
	public $subscribers = [];

	/**
	 * Check if the service provider provides a specific service.
	 *
	 * @param string $id The id of the service.
	 *
	 * @return bool
	 */
	public function provides( string $id ): bool {
		return in_array( $id, $this->provides, true );
	}

	/**
	 * Registers items with the container
	 *
	 * @return void
	 */
	public function register(): void {
		$this->getContainer()->add( Optin::class )
			->addArguments(
				[
					'backwpup',
					'manage_options',
				]
			);

		$this->getContainer()->add( TrackingPlugin::class )
			->addArguments(
				[
					'ca194771e8caa6fca7ff02896cded17d',
					BackWPup::get_plugin_data( 'version' ),
				]
			);
	}

	/**
	 * Returns the subscribers array
	 *
	 * @return array
	 */
	public function get_subscribers() {
		return $this->subscribers;
	}
}
