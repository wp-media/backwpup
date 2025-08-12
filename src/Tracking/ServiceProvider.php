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
		Tracking::class,
		Subscriber::class,
		Notices::class,
	];

	/**
	 * Subscribers provided by this provider
	 *
	 * @var array
	 */
	public $subscribers = [
		Subscriber::class,
	];

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
					'517e881edc2636e99a2ecf013d8134d3',
					BackWPup::get_plugin_data( 'version' ),
					'WP Media',
					'BackWPup',
				]
			);

		$this->getContainer()->add( Tracking::class )
			->addArguments(
				[
					$this->getContainer()->get( Optin::class ),
					$this->getContainer()->get( TrackingPlugin::class ),
					$this->getContainer()->get( 'option_adapter' ),
				]
			);

		$this->getContainer()->add( Notices::class )
			->addArguments(
				[
					$this->getContainer()->get( Optin::class ),
				]
			);

		$this->getContainer()->addShared( Subscriber::class )
			->addArguments(
				[
					$this->getContainer()->get( Tracking::class ),
					$this->getContainer()->get( Notices::class ),
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
