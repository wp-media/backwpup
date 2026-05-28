<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Log;

use WPMedia\BackWPup\Dependencies\League\Container\ServiceProvider\AbstractServiceProvider;

/**
 * Register shared log services.
 *
 * @package BackWPup
 */
class ServiceProvider extends AbstractServiceProvider {

	/**
	 * List of provided service identifiers.
	 *
	 * @var string[]
	 */
	protected $provides = [
		'log_facade',
		'log_reader',
		'log_renderer',
		'log_formatter',
		'log_writer',
		'log_excerpt_reader',
	];

	/**
	 * {@inheritdoc}
	 *
	 * @param string $id Service identifier.
	 */
	public function provides( string $id ): bool {
		return in_array( $id, $this->provides, true );
	}

	/**
	 * {@inheritdoc}
	 */
	public function register(): void {
		$this->getContainer()->addShared( 'log_facade', LogFacade::class )
			->addArguments(
				[
					'log_reader',
					'log_renderer',
					'log_formatter',
					'log_excerpt_reader',
					'log_writer',
				]
			);
		$this->getContainer()->addShared( 'log_reader', HtmlLogReader::class );
		$this->getContainer()->addShared( 'log_renderer', HtmlLogRenderer::class );
		$this->getContainer()->addShared( 'log_formatter', WpDateLogFormatter::class );
		$this->getContainer()->addShared( 'log_writer', HtmlLogWriter::class );
		$this->getContainer()->addShared( 'log_excerpt_reader', HtmlLogExcerptReader::class );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return array<int, mixed>
	 */
	public function get_subscribers() {
		return [];
	}
}
