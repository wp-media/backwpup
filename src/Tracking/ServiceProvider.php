<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Tracking;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BackWPup;
use WPMedia\BackWPup\Dependencies\League\Container\ServiceProvider\AbstractServiceProvider;
use WPMedia\BackWPup\Dependencies\WPMedia\Mixpanel\Optin;
use WPMedia\BackWPup\Dependencies\WPMedia\Mixpanel\TrackingPlugin;

class ServiceProvider extends AbstractServiceProvider {

	/**
	 * The Mixpanel project token/key used for tracking.
	 */
	public const MIXPANEL_KEY = '517e881edc2636e99a2ecf013d8134d3';

	/**
	 * Services provided by this provider
	 *
	 * @var array
	 */
	protected $provides = [
		Optin::class,
		TrackingPlugin::class,
		Tracking::class,
		McpTracking::class,
		Subscriber::class,
		McpTrackingSubscriber::class,
		Notices::class,
		NudgeTracking::class,
	];

	/**
	 * Subscribers provided by this provider
	 *
	 * @var array
	 */
	public $subscribers = [
		Subscriber::class,
		McpTrackingSubscriber::class,
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
		$plugin_name = BackWPup::get_plugin_data( 'name' ) . ' ' . BackWPup::get_plugin_data( 'version' );

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
					self::MIXPANEL_KEY,
					$plugin_name,
					'wp media',
					'backwpup',
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

		$this->getContainer()->add( McpTracking::class )
			->addArguments(
				[
					$this->getContainer()->get( Optin::class ),
					$this->getContainer()->get( TrackingPlugin::class ),
				]
			);

		$this->getContainer()->add( Notices::class )
			->addArguments(
				[
					$this->getContainer()->get( Optin::class ),
				]
			);

		$this->getContainer()->addShared( NudgeTracking::class )
			->addArguments(
				[
					$this->getContainer()->get( Optin::class ),
					$this->getContainer()->get( TrackingPlugin::class ),
				]
			);

		$this->getContainer()->addShared( Subscriber::class )
			->addArguments(
				[
					$this->getContainer()->get( Tracking::class ),
					$this->getContainer()->get( Notices::class ),
					$this->getContainer()->get( NudgeTracking::class ),
				]
			);

		$this->getContainer()->addShared( McpTrackingSubscriber::class )
			->addArguments(
				[
					$this->getContainer()->get( McpTracking::class ),
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
