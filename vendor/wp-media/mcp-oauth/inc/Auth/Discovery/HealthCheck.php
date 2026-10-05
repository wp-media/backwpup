<?php
/**
 * Site Health self-check for the .well-known OAuth discovery documents.
 *
 * On hosts that provision a physical `.well-known/acme-challenge/` directory
 * for Let's Encrypt auto-SSL (OVH, cPanel/AutoSSL, Plesk, and most managed-WP
 * hosts), the web server can 404 sibling `.well-known/oauth-*` paths before
 * WordPress ever runs — see the README "Hosting: .well-known conflicts"
 * section. No WordPress-level code change can influence that; this class is
 * a passive diagnostic only, registered as a single `direct` WordPress Site
 * Health test (the `site_status_tests` filter) that issues a loopback request
 * to both RFC discovery documents (`Endpoints`) and reports one combined
 * status.
 */

declare(strict_types=1);

namespace WPMedia\MCP\OAuth\Auth\Discovery;

use WP_Error;
use WPMedia\MCP\OAuth\Context;
use WPMedia\MCP\OAuth\Logging\McpLogger;

class HealthCheck {

	/**
	 * Site Health test key registered under the `direct` bucket.
	 */
	const TEST_KEY = 'wpmedia_mcp_oauth_wellknown_discovery';

	/**
	 * Transient key caching the combined self-check result.
	 */
	const TRANSIENT_KEY = 'wpmedia_mcp_oauth_wellknown_health_check';

	/**
	 * Cache TTL, in seconds, for the combined self-check result.
	 */
	const TRANSIENT_TTL = 5 * MINUTE_IN_SECONDS;

	/**
	 * Per-request timeout, in seconds, for each loopback discovery request.
	 */
	const FETCH_TIMEOUT = 5;

	/**
	 * Relative discovery document paths, keyed by a short display name.
	 *
	 * @var array<string, string>
	 */
	private const DOCUMENTS = [
		'oauth-protected-resource'   => '/.well-known/oauth-protected-resource',
		'oauth-authorization-server' => '/.well-known/oauth-authorization-server',
	];

	/**
	 * Status severity used to pick the combined result.
	 *
	 * @var array<string, int>
	 */
	private const STATUS_RANK = [
		'good'        => 0,
		'recommended' => 1,
		'critical'    => 2,
	];

	/**
	 * OAuth server context.
	 *
	 * @var Context
	 */
	private Context $context;

	/**
	 * Constructor.
	 *
	 * @param Context $context OAuth server context.
	 */
	public function __construct( Context $context ) {
		$this->context = $context;
	}

	/**
	 * Register the combined discovery-document self-check as a `direct` Site Health test.
	 *
	 * @param array<string, array<string, array<string, mixed>>> $tests Existing Site Health tests, keyed by bucket ('direct'|'async').
	 * @return array<string, array<string, array<string, mixed>>> Modified tests.
	 */
	public function add_test( array $tests ): array {
		$tests['direct'][ self::TEST_KEY ] = [
			'label' => __( 'MCP OAuth discovery documents', 'mcp-oauth' ),
			'test'  => [ $this, 'run_self_check' ],
		];

		return $tests;
	}

	/**
	 * Run the combined discovery-document self-check.
	 *
	 * @return array<string, mixed> Site Health test result (label, status, badge, description, actions, test).
	 */
	public function run_self_check(): array {
		if ( ! $this->context->is_enabled() ) {
			return $this->build_result(
				'good',
				[],
				__( 'The MCP OAuth server is intentionally disabled (the <code>wpmedia_mcp_oauth_server_enabled</code> filter returns false), so the .well-known discovery documents are not expected to be reachable.', 'mcp-oauth' )
			);
		}

		if ( '' === (string) get_option( 'permalink_structure' ) ) {
			return $this->build_result(
				'recommended',
				[],
				__( 'The .well-known OAuth discovery documents are served through a rewrite rule, which requires a pretty permalink structure. Enable one under Settings → Permalinks, then re-check this test.', 'mcp-oauth' )
			);
		}

		$cached = get_transient( self::TRANSIENT_KEY );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$worst_status = 'good';
		$failing      = [];

		foreach ( self::DOCUMENTS as $name => $path ) {
			$response = wp_safe_remote_get(
				home_url( $path ),
				[
					'timeout' => self::FETCH_TIMEOUT,
				]
			);

			$status = $this->classify( $response );

			if ( self::STATUS_RANK[ $status ] > self::STATUS_RANK[ $worst_status ] ) {
				$worst_status = $status;
			}

			if ( 'good' !== $status ) {
				$failing[] = $name;
			}
		}

		McpLogger::log(
			'DISCOVERY',
			'self-check result',
			[
				'status'  => $worst_status,
				'failing' => $failing,
			]
		);

		$result = $this->build_result( $worst_status, $failing );

		set_transient( self::TRANSIENT_KEY, $result, self::TRANSIENT_TTL );

		return $result;
	}

	/**
	 * Classify a single document's fetch result into a Site Health status.
	 *
	 * @param array<string, mixed>|WP_Error $response The wp_remote_get() response array, or a WP_Error.
	 * @return string One of 'good', 'recommended', 'critical'.
	 */
	private function classify( $response ): string {
		if ( is_wp_error( $response ) ) {
			return 'recommended';
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );

		if ( in_array( $status_code, [ 401, 403, 407 ], true ) ) {
			return 'recommended';
		}

		if ( 404 === $status_code ) {
			$powered_by  = (string) wp_remote_retrieve_header( $response, 'x-powered-by' );
			$redirect_by = (string) wp_remote_retrieve_header( $response, 'x-redirect-by' );

			if ( '' === $powered_by && false === stripos( $redirect_by, 'WordPress' ) ) {
				return 'critical';
			}

			return 'recommended';
		}

		if ( 200 === $status_code ) {
			$body    = (string) wp_remote_retrieve_body( $response );
			$decoded = json_decode( $body, true );

			if ( is_array( $decoded ) ) {
				return 'good';
			}
		}

		return 'recommended';
	}

	/**
	 * Build the Site Health result array for a given combined status.
	 *
	 * @param string   $status  One of 'good', 'recommended', 'critical'.
	 * @param string[] $failing Display names of the documents that did not report 'good'.
	 * @param string   $summary Optional override for the description's leading sentence
	 *                          (used by the disabled/plain-permalinks short-circuits).
	 * @return array<string, mixed>
	 */
	private function build_result( string $status, array $failing, string $summary = '' ): array {
		$loopback_caveat = __( 'This check runs from the server to itself. A "Good" result here does not guarantee external clients can reach these documents: a CDN, WAF, or reverse proxy in front of the site can still 404 these paths for external traffic while the server\'s own loopback request bypasses it. Verify with an external <code>curl</code> request after applying any server-config change.', 'mcp-oauth' );

		if ( '' !== $summary ) {
			$description = sprintf( '<p>%s</p>', $summary );
			$actions     = sprintf( '<p>%s</p>', $loopback_caveat );
		} elseif ( 'good' === $status ) {
			$description = sprintf(
				'<p>%s</p>',
				__( 'Both .well-known OAuth discovery documents (oauth-protected-resource and oauth-authorization-server) responded with HTTP 200 and valid JSON.', 'mcp-oauth' )
			);
			$actions     = sprintf( '<p>%s</p>', $loopback_caveat );
		} else {
			$document_list = implode( ', ', $failing );

			if ( 'critical' === $status ) {
				$description = sprintf(
					'<p>%s</p>',
					sprintf(
						/* translators: %s: comma-separated list of failing discovery document names. */
						__( 'The following .well-known discovery document(s) returned a bare 404 with no WordPress-originated response header: %s. This matches the fingerprint of a physical .well-known/acme-challenge/ directory (provisioned by the host for Let\'s Encrypt auto-SSL) intercepting the request before WordPress runs — likely the cause here, though this should be confirmed against the actual .well-known/acme-challenge/ directory on the server before concluding root cause.', 'mcp-oauth' ),
						esc_html( $document_list )
					)
				);

				$actions = sprintf(
					'<p>%s</p><p>%s</p><p>%s</p>',
					__( 'Apply the Apache or Nginx snippet from this library\'s README ("Hosting: .well-known conflicts") to re-enable routing for these two paths without touching acme-challenge/.', 'mcp-oauth' ),
					__( 'If a CDN or page cache is in front of the site, purge it after applying the fix — a stale cached 404 for these paths can otherwise persist even once the server config is corrected.', 'mcp-oauth' ),
					$loopback_caveat
				);
			} else {
				$description = sprintf(
					'<p>%s</p>',
					sprintf(
						/* translators: %s: comma-separated list of failing discovery document names. */
						__( 'The following .well-known discovery document(s) did not respond as expected: %s. This does not match the confirmed .well-known/acme-challenge/ interception fingerprint, so the cause is inconclusive from this check alone (it may be a timeout, an access wall such as HTTP Basic Auth or a WAF, or a WordPress-served error).', 'mcp-oauth' ),
						esc_html( $document_list )
					)
				);

				$actions = sprintf(
					'<p>%s</p><p>%s</p>',
					__( 'Check your error/access logs for the requests to these paths, and rule out an authentication wall (staging HTTP Basic Auth, a WAF, or bot mitigation) before assuming a routing problem.', 'mcp-oauth' ),
					$loopback_caveat
				);
			}
		}

		return [
			'label'       => __( 'MCP OAuth discovery documents', 'mcp-oauth' ),
			'status'      => $status,
			'badge'       => [
				'label' => __( 'Configuration', 'mcp-oauth' ),
				'color' => 'blue',
			],
			'description' => $description,
			'actions'     => $actions,
			'test'        => self::TEST_KEY,
		];
	}
}
