<?php

namespace WPMedia\BackWPup\Jobs\Frontend\API;

use WPMedia\BackWPup\API\Rest as RestInterface;
use WPMedia\BackWPup\Adapters\BackWPupHelpersAdapter;
use WPMedia\BackWPup\Adapters\JobAdapter;
use WP_REST_Response;
use WP_Error;

class Rest implements RestInterface {

	/**
	 * Instance of BackWPUpHelpersAdapter.
	 *
	 * @var BackWPupHelpersAdapter
	 */
	private $helper_adapter;

	/**
	 * Instance of JobAdapter.
	 *
	 * @var JobAdapter
	 */
	private $job_adapter;

	/**
	 * Constructor.
	 *
	 * @param BackWPupHelpersAdapter $helper_adapter
	 * @param JobAdapter             $job_adapter
	 */
	public function __construct( BackWPupHelpersAdapter $helper_adapter, JobAdapter $job_adapter ) {
		$this->job_adapter    = $job_adapter;
		$this->helper_adapter = $helper_adapter;
	}

	/**
	 * Registers the REST API routes for the BackWPup plugin.
	 *
	 * This method is responsible for defining the routes that the plugin
	 * exposes via the WordPress REST API. Each route should be registered
	 * with its corresponding callback and permissions.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			'backwpup/v1',
			'/getjobslist',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_jobs_list' ],
				'permission_callback' => [ $this, 'has_permission' ],
			]
		);
	}

	/**
	 * Checks if the current user has the necessary permissions to perform the action.
	 *
	 * @return bool True if the user has permission, false otherwise.
	 */
	public function has_permission(): bool {
		return current_user_can( 'backwpup' );
	}

	/**
	 * Get jobs list in HTML
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_jobs_list() {
		$jobs = $this->job_adapter->get_jobs();

		$html = '';

		foreach ( $jobs as $job ) {
			// Skip temp jobs.
			if ( isset( $job['tempjob'] ) && true === $job['tempjob'] ) {
				continue;
			}

			if ( isset( $job['backup_now'] ) && true === $job['backup_now'] ) {
				continue;
			}

			// Skip legacy jobs.
			if ( ! isset( $job['jobid'] ) || ( isset( $job['legacy'] ) && true === $job['legacy'] ) ) {
				continue;
			}
			$html .= $this->helper_adapter->component( 'job-item', [ 'job' => $job ], true );
		}

		return rest_ensure_response( $html );
	}
}
