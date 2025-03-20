<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Plugin;

use WPMedia\BackWPup\Dependencies\League\Container\Container;
use WPMedia\BackWPup\Dependencies\League\Container\ServiceProvider\ServiceProviderInterface;
use WPMedia\BackWPup\EventManagement\EventManager;
use WPMedia\BackWPup\EventManagement\SubscriberInterface;

class Plugin {
	/**
	 * Container instance.
	 *
	 * @var Container
	 */
	private $container;

	/**
	 * Is the plugin loaded
	 *
	 * @var boolean
	 */
	private $loaded = false;

	/**
	 * Instantiate the class.
	 *
	 * @since 1.9
	 *
	 * @param Container $container Instance of the container.
	 */
	public function __construct( Container $container ) {
		$this->container = $container;

		add_filter( 'backwpup_container', [ $this, 'get_container' ] );
	}

	/**
	 * Returns the container instance.
	 *
	 * @return Container
	 */
	public function get_container() {
		return $this->container;
	}

	/**
	 * Checks if the plugin is loaded
	 *
	 * @return boolean
	 */
	private function is_loaded(): bool {
		return $this->loaded;
	}

	/**
	 * Plugin init.
	 *
	 * @param array $providers Array of service providers.
	 *
	 * @since 1.9
	 */
	public function init( $providers ) {
		if ( $this->is_loaded() ) {
			return;
		}

		$this->container->addShared(
			'event_manager',
			function () {
				return new EventManager();
			}
		);

		foreach ( $providers as $service_provider ) {
			$provider_instance = new $service_provider();
			$this->container->addServiceProvider( $provider_instance );

			// Load each service provider's subscribers if found.
			$this->load_subscribers( $provider_instance );
		}

		$this->loaded = true;
	}

	/**
	 * Load list of event subscribers from service provider.
	 *
	 * @param ServiceProviderInterface $service_provider Instance of service provider.
	 *
	 * @return void
	 */
	private function load_subscribers( ServiceProviderInterface $service_provider ) {
		if ( empty( $service_provider->get_subscribers() ) ) {
			return;
		}

		foreach ( $service_provider->get_subscribers() as $subscriber ) {
			$subscriber_object = $this->container->get( $subscriber );

			if ( $subscriber_object instanceof SubscriberInterface ) {
				$this->container->get( 'event_manager' )->add_subscriber( $subscriber_object );
			}
		}
	}
}
