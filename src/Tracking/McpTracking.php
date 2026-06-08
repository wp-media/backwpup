<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Tracking;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * MCP-specific tracking for Model Context Protocol abilities.
 *
 * Tracks usage, performance, and errors for MCP abilities to understand
 * user adoption and identify issues.
 */
class McpTracking extends BaseTracking {

	/**
	 * Track MCP ability execution.
	 *
	 * @param string $ability_id   Ability ID (e.g., 'backwpup/run-job').
	 * @param string $ability_name Tool name (e.g., 'backwpup_run_job').
	 * @param mixed  $result       Execution result (array or WP_Error).
	 * @param float  $start_time   Execution start time (microtime).
	 * @param array  $input_params Sanitized input parameters.
	 *
	 * @return void
	 */
	public function track_ability_executed(
		string $ability_id,
		string $ability_name,
		$result,
		float $start_time,
		array $input_params = []
	): void {
		if ( ! $this->can_track() ) {
			return;
		}

		$execution_time_ms = ( microtime( true ) - $start_time ) * 1000;
		$is_error          = is_wp_error( $result );

		$properties = array_merge(
			$this->get_default_event_properties( 'wp_plugin_mcp' ),
			[
				'ability_id'        => $ability_id,
				'ability_name'      => $ability_name,
				'success'           => ! $is_error,
				'execution_time_ms' => round( $execution_time_ms, 2 ),
				'input_params'      => $this->sanitize_input_params( $input_params ),
			]
		);

		// Add error information if execution failed.
		if ( $is_error ) {
			$properties['error_code']    = $result->get_error_code();
			$properties['error_message'] = $result->get_error_message();
		}

		// Add user information if available.
		$user = wp_get_current_user();
		if ( $user->exists() ) {
			$this->identify_user( $user );
			$properties['user_id'] = $user->ID;
		}

		$this->mixpanel->track(
			'MCP Ability Executed',
			$properties
		);
	}

	/**
	 * Track MCP ability permission denial.
	 *
	 * @param string $ability_id            Ability ID.
	 * @param string $ability_name          Tool name.
	 * @param string $required_capability   Required capability.
	 *
	 * @return void
	 */
	public function track_permission_denied(
		string $ability_id,
		string $ability_name,
		string $required_capability
	): void {
		if ( ! $this->can_track() ) {
			return;
		}

		$user = wp_get_current_user();

		$properties = array_merge(
			$this->get_default_event_properties( 'wp_plugin_mcp' ),
			[
				'ability_id'          => $ability_id,
				'ability_name'        => $ability_name,
				'required_capability' => $required_capability,
				'user_id'             => $user->ID,
				'user_role'           => $this->get_user_primary_role( $user ),
				'permission_denied'   => true,
			]
		);

		$this->identify_user( $user );

		$this->mixpanel->track(
			'MCP Ability Permission Denied',
			$properties
		);
	}

	/**
	 * Track MCP-initiated backup job.
	 *
	 * Uses the same event name as the standard backup path ("Scheduled Backup Job Started")
	 * so Mixpanel can aggregate all triggers; the context property (wp_plugin_mcp) distinguishes the source.
	 *
	 * @param int|null $job_id        Job ID (null for default job).
	 * @param string   $job_name      Job name.
	 * @param bool     $is_default    Whether it's the default "Backup Now" job.
	 * @param array    $destinations  Storage destinations.
	 * @param array    $job_types     Backup types.
	 *
	 * @return void
	 */
	public function track_mcp_backup_triggered(
		?int $job_id,
		string $job_name,
		bool $is_default,
		array $destinations,
		array $job_types
	): void {
		if ( ! $this->can_track() ) {
			return;
		}

		$user = wp_get_current_user();

		$properties = array_merge(
			$this->get_default_event_properties( 'wp_plugin_mcp' ),
			[
				'job_id'         => $job_id,
				'job_name'       => $job_name,
				'is_default_job' => $is_default,
				'initiated_via'  => 'mcp',
				'destinations'   => $destinations,
				'job_types'      => $job_types,
			]
		);

		if ( $user->exists() ) {
			$this->identify_user( $user );
			$properties['user_id'] = $user->ID;
		}

		$this->mixpanel->track(
			'Scheduled Backup Job Started',
			$properties
		);
	}
}
