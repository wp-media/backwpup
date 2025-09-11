<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Beta;

use BackWPup;
use WPMedia\BackWPup\Dependencies\League\Container\ServiceProvider\AbstractServiceProvider;
use WPMedia\Beta\Optin;
use WPMedia\Beta\Beta;

class ServiceProvider extends AbstractServiceProvider {
	/**
	 * Services provided by this provider
	 *
	 * @var array
	 */
	protected $provides = [
		Optin::class,
		Beta::class,
		Subscriber::class,
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

		$this->getContainer()->add( Beta::class )
			->addArguments(
				[
					Optin::class,
					\BackWPup::get_plugin_data( 'basename' ),
					'backwpup',
					\BackWPup::get_plugin_data( 'version' ),
				]
			);

		$this->getContainer()->add( Subscriber::class )
			->addArguments(
				[
					Optin::class,
					Beta::class,
					'backwpup_adapter',
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
