<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\MCP;

use WPMedia\BackWPup\Abilities\MCP\DocsOverview;
use WPMedia\BackWPup\EventManagement\SubscriberInterface;

/**
 * MCP Abilities Subscriber
 *
 * Registers MCP-specific abilities like documentation resources.
 */
class AbilitiesSubscriber implements SubscriberInterface {

	/**
	 * DocsOverview ability instance
	 *
	 * @var DocsOverview
	 */
	private DocsOverview $docs_overview;

	/**
	 * Constructor
	 *
	 * @param DocsOverview $docs_overview DocsOverview ability instance.
	 */
	public function __construct( DocsOverview $docs_overview ) {
		$this->docs_overview = $docs_overview;
	}

	/**
	 * Get the events to subscribe to.
	 *
	 * @return array
	 */
	public static function get_subscribed_events(): array {
		return [
			'wp_abilities_api_init' => 'register_abilities',
		];
	}

	/**
	 * Register MCP abilities
	 *
	 * @return void
	 */
	public function register_abilities(): void {
		$this->docs_overview->register();
	}
}
