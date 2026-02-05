<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Admin\Chatbot\API;

use WPMedia\BackWPup\EventManagement\SubscriberInterface;

class ChatbotRestSubscriber implements SubscriberInterface {

	/**
	 * Rest instance.
	 *
	 * @var ChatbotRest Instance
	 */
	private ChatbotRest $rest;

	/**
	 * Constructor.
	 *
	 * @param ChatbotRest $rest
	 */
	public function __construct( ChatbotRest $rest ) {
		$this->rest = $rest;
	}

	/**
	 * Subscriber events.
	 *
	 * @return string[]
	 */
	public static function get_subscribed_events(): array {
		return [
			'rest_api_init' => 'register_routes',
		];
	}

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		$this->rest->register_routes();
	}
}
