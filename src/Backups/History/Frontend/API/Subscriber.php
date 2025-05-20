<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Backups\History\Frontend\API;

use WPMedia\BackWPup\EventManagement\SubscriberInterface;
use WPMedia\BackWPup\Backups\History\Frontend\API\Rest as FrontEndRest;

class Subscriber implements SubscriberInterface {
	/**
	 * An instance of the FrontEndRest class used to handle REST API interactions
	 *                         for the frontend backup history functionality.
	 *
	 * @var FrontEndRest $rest
	 */
	private FrontEndRest $rest;

	/**
	 * Constructor for the Subscriber class.
	 *
	 * @param FrontEndRest $rest An instance of the FrontEndRest class used to handle REST API interactions.
	 */
	public function __construct( FrontEndRest $rest ) {
		$this->rest = $rest;
	}

	/**
	 * Retrieves the list of events that this subscriber is subscribed to.
	 *
	 * This method is used to define the events and their corresponding
	 * callback methods that this class will listen to.
	 *
	 * @return array An associative array where the keys are event names
	 *               and the values are the methods to be called when the
	 *               event is triggered.
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
