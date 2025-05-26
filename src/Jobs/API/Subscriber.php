<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Jobs\API;

use WPMedia\BackWPup\EventManagement\SubscriberInterface;
use WPMedia\BackWPup\Jobs\API\Rest;

class Subscriber implements SubscriberInterface {

	/**
	 * REST API handler instance.
	 *
	 * @var Rest
	 */
	private Rest $rest;

	/**
	 * Subscriber constructor.
	 *
	 * @param Rest $rest REST API handler instance.
	 */
	public function __construct( Rest $rest ) {
		$this->rest = $rest;
	}

	/**
	 * Return an array of events that this subscriber wants to listen to.
	 *
	 * @return array
	 */
	public static function get_subscribed_events(): array {
		return [
			'rest_api_init' => 'register_routes',
		];
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		$this->rest->register_routes();
	}
}
