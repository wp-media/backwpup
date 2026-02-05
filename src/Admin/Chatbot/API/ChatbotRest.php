<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Admin\Chatbot\API;

use WP_HTTP_Response;
use WP_REST_Request;

use WPMedia\BackWPup\Admin\Chatbot\ContextSnapshotBuilder;
use WPMedia\BackWPup\API\Rest as RestInterface;

class ChatbotRest implements RestInterface {

	const CHATBOT_ENDPOINT = '/chatbot-context';

	/**
	 * Context Snapshot Builder instance.
	 *
	 * @var ContextSnapshotBuilder Instance
	 */
	private ContextSnapshotBuilder $snapshot_builder;

	/**
	 * Rest instance.
	 *
	 * @param ContextSnapshotBuilder $snapshot_builder Instance.
	 */
	public function __construct(
		ContextSnapshotBuilder $snapshot_builder
	) {
		$this->snapshot_builder = $snapshot_builder;
	}

	/**
	 * Check permissions.
	 *
	 * @return bool
	 */
	public function has_permission(): bool {
		return is_user_logged_in() && current_user_can( 'backwpup' );
	}

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			self::ROUTE_NAMESPACE,
			self::CHATBOT_ENDPOINT,
			[
				[
					'methods'             => 'POST',
					'callback'            => [ $this, 'create_context' ],
					'permission_callback' => [ $this, 'has_permission' ],
				],
				[
					'methods'             => 'GET',
					'callback'            => [ $this, 'get_context' ],
					'permission_callback' => [ $this, 'has_context_token_permission' ],
					'args'                => [
						'context_id'    => [
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						],
						'context_token' => [
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						],
					],
				],
			]
		);
	}

	/**
	 * Creates a context snapshot.
	 *
	 * @param WP_REST_Request $request
	 * @return \WP_Error|WP_HTTP_Response|\WP_REST_Response
	 */
	public function create_context( WP_REST_Request $request ) {
		$context_id    = wp_generate_uuid4();
		$context_token = wp_generate_password( 32, false, false );
		$ttl           = 10 * MINUTE_IN_SECONDS;

		$context = $this->snapshot_builder->build( 5 );

		set_site_transient(
			'backwpup_chatbot_context_' . $context_id,
			[
				'token'   => $context_token,
				'payload' => $context,
			],
			$ttl
		);

		/**
		 * Fires when the context is created (after the contact support button is clicked).
		 */
		do_action( 'backwpup_track_support_tool_button_clicked' );

		return new \WP_REST_Response(
			[
				'context_id'    => $context_id,
				'context_token' => $context_token,
				'expires_in'    => $ttl,
				'payload'       => $context,
			],
			201
		);
	}

	/**
	 * Only allow read if token matches the stored one.
	 *
	 * @param WP_REST_Request $request
	 * @return bool
	 */
	public function has_context_token_permission( WP_REST_Request $request ): bool {
		$context_id    = (string) $request->get_param( 'context_id' );
		$context_token = (string) $request->get_param( 'context_token' );

		if ( '' === $context_id || '' === $context_token ) {
			return false;
		}

		$stored = get_site_transient( 'backwpup_chatbot_context_' . $context_id );
		if ( ! is_array( $stored ) || empty( $stored['token'] ) ) {
			return false;
		}

		return hash_equals( (string) $stored['token'], $context_token );
	}

	/**
	 * Returns the context snapshot associated with context_id.
	 *
	 * @param WP_REST_Request $request
	 * @return \WP_Error|WP_HTTP_Response|\WP_REST_Response
	 */
	public function get_context( WP_REST_Request $request ) {
		$context_id = (string) $request->get_param( 'context_id' );
		$stored     = get_site_transient( 'backwpup_chatbot_context_' . $context_id );

		if ( ! is_array( $stored ) || empty( $stored['payload'] ) ) {
			return new \WP_REST_Response(
				[ 'message' => __( 'Context not found or expired.', 'backwpup' ) ],
				404
			);
		}

		return new \WP_REST_Response( $stored['payload'], 200 );
	}
}
