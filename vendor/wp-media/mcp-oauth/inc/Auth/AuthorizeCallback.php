<?php
/**
 * Post-login Authorization Callback — Consent Screen.
 *
 * Handles GET /oauth/authorize-callback — called by WordPress after the user
 * authenticates.  Looks up the state transient (which already carries the
 * validated client display data captured at authorize time), refreshes the
 * transient TTL to give the user time to read the screen, then renders a
 * standalone HTML consent page.
 *
 * The user's Allow/Deny choice is handled by ConsentEndpoint (POST /oauth/consent).
 */

declare(strict_types=1);

namespace WPMedia\MCP\OAuth\Auth;

use WPMedia\MCP\OAuth\Logging\McpLogger;
use WPMedia\MCP\OAuth\Views\Render;

/**
 * Authorize Callback — renders the consent screen.
 */
class AuthorizeCallback {
	/**
	 * How long (seconds) the state transient lives once the consent screen is shown.
	 * Replaces the original 60 s authorize-window TTL so the user has time to decide.
	 */
	const CONSENT_TTL = 300;

	/**
	 * View renderer.
	 *
	 * @var Render
	 */
	private Render $render;

	/**
	 * Constructor.
	 *
	 * @param Render $render View renderer.
	 */
	public function __construct( Render $render ) {
		$this->render = $render;
	}

	/**
	 * Handle the post-login callback — show the consent screen.
	 *
	 * @return void
	 */
	public function handle_request(): void {
		$state = sanitize_text_field( wp_unslash( $_GET['state'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth state token echoed back by the login redirect, not a WP nonce-protected form.

		McpLogger::log(
			'CALLBACK',
			'authorize-callback received',
			[
				'is_logged_in' => is_user_logged_in() ? 'yes' : 'no',
				'user_id'      => is_user_logged_in() ? get_current_user_id() : 0,
				'has_state'    => '' !== $state ? 'yes' : 'no',
				'remote_addr'  => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
			]
		);

		if ( ! is_user_logged_in() ) {
			McpLogger::log( 'CALLBACK', 'rejected: user not logged in' );
			wp_die( esc_html__( 'You must be logged in to authorise an MCP session.', 'mcp-oauth' ), esc_html__( 'OAuth Error', 'mcp-oauth' ), [ 'response' => 401 ] );
		}

		if ( '' === $state ) {
			McpLogger::log( 'CALLBACK', 'rejected: missing state' );
			wp_die( esc_html__( 'Missing state parameter.', 'mcp-oauth' ), esc_html__( 'OAuth Error', 'mcp-oauth' ), [ 'response' => 400 ] );
		}

		$state_key  = 'mcp_oauth_state_' . $state;
		$state_data = get_transient( $state_key );

		if ( false === $state_data || ! is_array( $state_data ) ) {
			McpLogger::log( 'CALLBACK', 'rejected: state transient not found or expired', [ 'state' => $state ] );
			wp_die( esc_html__( 'Invalid or expired state. Please restart the authorization flow.', 'mcp-oauth' ), esc_html__( 'OAuth Error', 'mcp-oauth' ), [ 'response' => 400 ] );
		}

		// Client display data was captured and validated by AuthorizeEndpoint and
		// stored in the state transient. Reusing it avoids a second CIMD lookup —
		// no network round-trip, and no spurious failure if the publisher is
		// briefly unreachable between login and consent.
		$client_id = (string) ( $state_data['client_id'] ?? '' );
		$client    = [
			'client_id'   => $client_id,
			'client_name' => (string) ( $state_data['client_name'] ?? '' ),
			'client_uri'  => (string) ( $state_data['client_uri'] ?? '' ),
			'verified'    => ! empty( $state_data['verified'] ),
			'publisher'   => (string) ( $state_data['publisher'] ?? '' ),
		];

		// Refresh state TTL — the user now has CONSENT_TTL seconds to decide.
		set_transient( $state_key, $state_data, self::CONSENT_TTL );

		McpLogger::log(
			'CALLBACK',
			'showing consent screen',
			[
				'user_id'     => get_current_user_id(),
				'client_id'   => $client_id,
				'client_name' => $client['client_name'],
				'verified'    => $client['verified'] ? 'yes' : 'no',
			]
		);

		$this->render_consent_screen( $state, $client );
	}

	/**
	 * Assemble the consent-screen view model and render it.
	 *
	 * @param string               $state  OAuth state token.
	 * @param array<string, mixed> $client Resolved CIMD client record.
	 * @return void
	 */
	private function output_consent_screen( string $state, array $client ): void {
		nocache_headers();

		// Escaped at each output site inside the template. URLs are esc_url'd
		// here since they're only ever emitted into href/action attributes.
		$client_name = (string) ( $client['client_name'] ?? '' );
		$client_id   = esc_url( (string) ( $client['client_id'] ?? '' ) );
		$client_uri  = esc_url( (string) ( $client['client_uri'] ?? '' ) );
		$verified    = ! empty( $client['verified'] );
		$publisher   = (string) ( $client['publisher'] ?? '' );
		$site_name   = (string) get_bloginfo( 'name' );
		$consent_url = esc_url( home_url( '/oauth/consent' ) ); // Rewrite endpoint: home_url(), not get_site_url().

		// The display name links to client_uri if available, otherwise client_id.
		$display_href = '' !== $client_uri ? $client_uri : $client_id;

		$this->render->view(
			'consent-screen',
			[
				'state'        => $state,
				'client_name'  => $client_name,
				'client_id'    => $client_id,
				'client_uri'   => $client_uri,
				'verified'     => $verified,
				'publisher'    => $publisher,
				'site_name'    => $site_name,
				'consent_url'  => $consent_url,
				'display_href' => $display_href,
			]
		);
	}

	/**
	 * Render the consent screen, then terminate the request.
	 *
	 * @param string               $state  OAuth state token.
	 * @param array<string, mixed> $client Resolved CIMD client record.
	 * @return void
	 */
	private function render_consent_screen( string $state, array $client ): void {
		$this->output_consent_screen( $state, $client );
		exit;
	}
}
