<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Backups\API;

use WPMedia\BackWPup\EventManagement\SubscriberInterface;

class Subscriber implements SubscriberInterface {
	/**
	 * An instance of the Rest class used for handling REST API interactions.
	 *
	 * @var Rest
	 */
	private Rest $rest;

	/**
	 * Constructor for the Subscriber class.
	 *
	 * @param Rest $rest An instance of the Rest class used for handling REST API interactions.
	 */
	public function __construct( Rest $rest ) {
		$this->rest = $rest;
	}

	/**
	 * Retrieves the list of events that this subscriber is interested in.
	 *
	 * This method is typically used to define the events and their corresponding
	 * callback methods that the subscriber will handle.
	 *
	 * @return array An associative array where the keys are event names and the values
	 *               are the methods or callables to be executed when the event is triggered.
	 */
	public static function get_subscribed_events(): array {
		return [
			'rest_api_init' => 'register_routes',
		];
	}

	/**
	 * Registers the routes for the API subscriber.
	 *
	 * This method is responsible for defining the API routes
	 * that the subscriber will handle.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		$this->rest->register_routes();
	}
}
