<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\MCP;

use WPMedia\BackWPup\Abilities\MCP\DocsOverview;
use WPMedia\BackWPup\Dependencies\League\Container\ServiceProvider\AbstractServiceProvider;

class ServiceProvider extends AbstractServiceProvider {

	/**
	 * Services provided by this provider.
	 *
	 * @var array
	 */
	protected $provides = [
		'mcp_config_subscriber',
		'mcp_docs_overview_ability',
		'mcp_abilities_subscriber',
	];

	/**
	 * Register services
	 *
	 * @return void
	 */
	public function register(): void {
		$this->getContainer()->addShared( 'mcp_config_subscriber', ConfigSubscriber::class );

		$this->getContainer()->addShared( 'mcp_docs_overview_ability', DocsOverview::class );

		$this->getContainer()->addShared( 'mcp_abilities_subscriber', AbilitiesSubscriber::class )
			->addArguments(
				[
					'mcp_docs_overview_ability',
				]
				);
	}

	/**
	 * Returns the subscribers array
	 *
	 * @return array
	 */
	public function get_subscribers(): array {
		return [
			'mcp_config_subscriber',
			'mcp_abilities_subscriber',
		];
	}

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
}
