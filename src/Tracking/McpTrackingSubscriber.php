<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Tracking;

use WPMedia\BackWPup\EventManagement\SubscriberInterface;

/**
 * Subscriber for MCP tracking events
 *
 * Hooks into WordPress actions fired by MCP abilities and delegates
 * tracking to the McpTracking service.
 */
class McpTrackingSubscriber implements SubscriberInterface {

	/**
	 * McpTracking instance
	 *
	 * @var McpTracking
	 */
	private McpTracking $mcp_tracking;

	/**
	 * Constructor
	 *
	 * @param McpTracking $mcp_tracking McpTracking instance.
	 */
	public function __construct( McpTracking $mcp_tracking ) {
		$this->mcp_tracking = $mcp_tracking;
	}

	/**
	 * Get the events to subscribe to.
	 *
	 * @return array
	 */
	public static function get_subscribed_events(): array {
		return [
			'backwpup_mcp_ability_executed'  => [ 'on_ability_executed', 10, 5 ],
			'backwpup_mcp_permission_denied' => [ 'on_permission_denied', 10, 3 ],
			'backwpup_mcp_backup_triggered'  => [ 'on_backup_triggered', 10, 5 ],
		];
	}

	/**
	 * Handle ability executed event
	 *
	 * @param string $ability_id   Ability ID.
	 * @param string $ability_name Ability tool name.
	 * @param mixed  $result       Execution result.
	 * @param float  $start_time   Execution start time.
	 * @param array  $input_params Input parameters.
	 *
	 * @return void
	 */
	public function on_ability_executed(
		string $ability_id,
		string $ability_name,
		$result,
		float $start_time,
		array $input_params
	): void {
		$this->mcp_tracking->track_ability_executed(
			$ability_id,
			$ability_name,
			$result,
			$start_time,
			$input_params
		);
	}

	/**
	 * Handle permission denied event
	 *
	 * @param string $ability_id          Ability ID.
	 * @param string $ability_name        Ability tool name.
	 * @param string $required_capability Required capability.
	 *
	 * @return void
	 */
	public function on_permission_denied(
		string $ability_id,
		string $ability_name,
		string $required_capability
	): void {
		$this->mcp_tracking->track_permission_denied(
			$ability_id,
			$ability_name,
			$required_capability
		);
	}

	/**
	 * Handle MCP backup triggered event
	 *
	 * @param int|null $job_id       Job ID.
	 * @param string   $job_name     Job name.
	 * @param bool     $is_default   Whether it's a default job.
	 * @param array    $destinations Storage destinations.
	 * @param array    $job_types    Backup types.
	 *
	 * @return void
	 */
	public function on_backup_triggered(
		?int $job_id,
		string $job_name,
		bool $is_default,
		array $destinations,
		array $job_types
	): void {
		$this->mcp_tracking->track_mcp_backup_triggered(
			$job_id,
			$job_name,
			$is_default,
			$destinations,
			$job_types
		);
	}
}
