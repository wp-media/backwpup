<?php

namespace WPMedia\BackWPup\Backups\History\Frontend\API;

use WPMedia\BackWPup\API\Rest as RestInterface;
use WPMedia\BackWPup\Adapters\BackWPupAdapter;
use WPMedia\BackWPup\Adapters\OptionAdapter;
use WPMedia\BackWPup\Adapters\BackWPupHelpersAdapter;
use WP_REST_Request;
use WP_REST_Response;

class Rest implements RestInterface {
	/**
	 * BackWPupAdapter instance.
	 *
	 * @var BackWPupAdapter
	 */
	private $backwpup_adapter;

	/**
	 * Option adapter instance.
	 *
	 * @var OptionAdapter
	 */
	private $option_adapter;

	/**
	 * BackWPupHelpersAdapter instance.
	 *
	 * @var BackWPupHelpersAdapter
	 */
	private $helpers_adapter;

	/**
	 * Constructor for the Rest class.
	 *
	 * @param BackWPupAdapter        $backups_adapter   Adapter for handling backup operations.
	 * @param OptionAdapter          $option_adapter    Adapter for managing options.
	 * @param BackWPupHelpersAdapter $helpers_adapter   Adapter for helper functions.
	 */
	public function __construct( BackWPupAdapter $backups_adapter, OptionAdapter $option_adapter, BackWPupHelpersAdapter $helpers_adapter ) {
		$this->backwpup_adapter = $backups_adapter;
		$this->option_adapter   = $option_adapter;
		$this->helpers_adapter  = $helpers_adapter;
	}

	/**
	 * Registers the REST API routes for the Backups History Frontend.
	 *
	 * This method is responsible for defining the routes that will be
	 * available in the REST API for interacting with the backups history
	 * frontend functionality.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			self::ROUTE_NAMESPACE,
			'/backups',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'get_backups_list' ],
				'permission_callback' => [ $this, 'has_permission' ],
				'args'                => [
					'page'   => [
						'default'           => 1,
						'sanitize_callback' => 'absint',
						'validate_callback' => function ( $param ) {
							return is_numeric( $param ) && $param >= 1; },
					],
					'length' => [
						'default'           => 10,
						'sanitize_callback' => 'absint',
						'validate_callback' => function ( $param ) {
							return is_numeric( $param ) && $param >= 1; },
					],
				],
			]
		);

		register_rest_route(
			self::ROUTE_NAMESPACE,
			'/pagination',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'get_pagination' ],
				'permission_callback' => [ $this, 'has_permission' ],
				'args'                => [
					'page'      => [
						'default'           => 1,
						'sanitize_callback' => 'absint',
						'validate_callback' => function ( $param ) {
							return is_numeric( $param ) && $param >= 1; },
					],
					'max_pages' => [
						'default'           => 10,
						'sanitize_callback' => function ( $param ) {
							$val = absint( $param );
							return $val < 1 ? 1 : $val;
						},
						'validate_callback' => function ( $param ) {
							return is_numeric( $param ) && absint( $param ) >= 0;
						},
					],
				],
			]
		);
	}
	/**
	 * Checks if the current user has the necessary permissions to access this functionality.
	 *
	 * @return bool True if the user has permission, false otherwise.
	 */
	public function has_permission(): bool {
		return current_user_can( 'backwpup' );
	}

	/**
	 * Get registered destinations
	 *
	 * @return array
	 */
	protected function get_registered_destinations(): array {
		return $this->backwpup_adapter->get_registered_destinations();
	}

	/**
	 * Retrieves a list of backups and returns them in a paginated format.
	 *
	 * @param WP_REST_Request $request The REST API request object containing parameters.
	 *
	 * @return WP_REST_Response The response object containing the backups HTML or an error message.
	 *
	 * The function performs the following steps:
	 * - Retrieves pagination parameters (`page` and `length`) from the request.
	 * - Fetches job IDs and iterates through each job to gather backup data.
	 * - Processes backup items for each destination associated with a job.
	 * - Ensures backups are unique by filtering duplicates based on `stored_on` and `filename`.
	 * - Sorts backups by their timestamp in descending order.
	 * - Paginates the backups based on the provided `page` and `length` parameters.
	 * - Generates HTML for the backups table rows and includes a hidden input for the total number of backups.
	 * - Returns a JSON response with the generated HTML or an error message if no backups are found.
	 *
	 * @throws \WP_Error If there is an issue with the request or processing backups.
	 */
	public function get_backups_list( WP_REST_Request $request ) {
		$params                  = $request->get_params();
		$page                    = $params['page'] ?? 1;
		$length                  = $params['length'] ?? 10;
		$start                   = $page * $length - $length;
		$jobs_ids                = $this->option_adapter->get_job_ids();
		$backups                 = [];
		$registered_destinations = $this->get_registered_destinations();

		foreach ( $jobs_ids as $a_job_id ) {
			$job = $this->option_adapter->get_job( $a_job_id );
			if ( ! $job ) {
				continue;
			}
			$dests    = $job['destinations'] ?? [];
			$job_data = [
				'id'       => $a_job_id,
				'name'     => $job['name'],
				'type'     => $job['activetype'],
				'data'     => [ 'Unknown' ],
				'logfile'  => $job['logfile'],
				'last_run' => $job['lastrun'] ?? null,
			];
			foreach ( $dests as $dest ) {
				if ( empty( $registered_destinations[ $dest ]['class'] ) ) {
					continue;
				}
				$dest_object = $this->backwpup_adapter->get_destination( $dest );
				if ( is_array( $dest_object ) ) {
					continue;
				}
				$items   = $dest_object->file_get_list( $a_job_id . '_' . $dest );
				$items   = $this->helpers_adapter->process_backup_items( $items, $job_data, $dest, $page );
				$backups = array_merge( $backups, $items );
			}
		}
		if ( 0 !== count( $jobs_ids ) ) {
			$default_location = $this->backwpup_adapter->get_destination( 'FOLDER' );
			if ( ! is_array( $default_location ) ) {
				$items   = $default_location->file_get_list();
				$items   = $this->helpers_adapter->process_backup_items( $items, $job_data, 'FOLDER', $page );
				$backups = array_merge( $backups, $items );
			}
		}
		$unique_backups = [];
		foreach ( $backups as $item ) {
			$key = $item['stored_on'] . '|' . $item['filename'];
			if ( ! isset( $seen[ $key ] ) ) {
				$seen[ $key ]     = true;
				$unique_backups[] = $item;
			}
		}
		$backups = $unique_backups;
		usort(
			$backups,
			function ( $a, $b ) {
				return $b['time'] <=> $a['time'];
			}
		);

		$backups         = array_slice( $backups, $start, $length );
		$html            = '';
		$backups         = wpm_apply_filters_typed( 'array', 'backwpup_backups_list', $backups );
		$nb_totalbackups = count( $backups );

		foreach ( $backups as $backup ) {
			if ( 'wpcron' === $backup['type'] ) {
				$backup['type'] = __( 'Scheduled', 'backwpup' );
			}
			$html .= $this->helpers_adapter->component( 'table-row-backups', [ 'backup' => $backup ], true );
		}
		$html .= $this->helpers_adapter->component(
			'form/hidden',
			[
				'name'  => 'nb_backups',
				'value' => $nb_totalbackups,
			],
			true
		);

		if ( empty( $html ) ) {
			return rest_ensure_response(
				[
					'success' => false,
					'data'    => '',
					'message' => __( 'No backups found.', 'backwpup' ),
				]
			);
		}

		return rest_ensure_response(
			[
				'success' => true,
				'data'    => $html,
			]
		);
	}

	/**
	 * Get the pagination of the history table.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function get_pagination( WP_REST_Request $request ): WP_REST_Response {
		$params    = $request->get_params();
		$page      = $params['page'] ?? 1;
		$max_pages = $params['max_pages'] ?? 10;
		$html      = $this->helpers_adapter->component(
			'navigation/pagination',
			[
				'max_pages'    => $max_pages,
				'trigger'      => 'table-pagination',
				'class'        => 'max-md:hidden',
				'current_page' => $page,
			],
			true
		);
		if ( ! empty( $html ) ) {
			return rest_ensure_response(
				[
					'success' => true,
					'data'    => $html,
				]
				);
		} else {
			return rest_ensure_response(
				[
					'success' => false,
					'data'    => '',
					'message' => __( 'No pagination found.', 'backwpup' ),
				]
				);
		}
	}
}
