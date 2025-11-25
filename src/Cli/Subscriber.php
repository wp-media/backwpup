<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Cli;

use WPMedia\BackWPup\EventManagement\SubscriberInterface;

class Subscriber implements SubscriberInterface {
	/**
	 * Array of commands.
	 *
	 * @var array $commands
	 */
	private array $commands;

	/**
	 * Subscriber constructor.
	 *
	 * @param array $commands Array of commands.
	 * @return void
	 */
	public function __construct( array $commands ) {
		$this->commands = $commands;
	}

	/**
	 * Return an array of events that this subscriber wants to listen to.
	 *
	 * @return array
	 */
	public static function get_subscribed_events(): array {
		return [
			'plugins_loaded' => [ 'register_commands', 500 ],
		];
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register_commands(): void {
		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
			return;
		}
		foreach ( $this->commands as $command ) {
			if ( $command instanceof Commands\Command ) {
				\WP_CLI::add_command( 'backwpup ' . $command->get_name(), $command, $command->get_args() );
			}
		}
	}
}
