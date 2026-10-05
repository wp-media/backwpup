<?php
/**
 * MCP OAuth Bootstrap.
 *
 * Single centralized entry point for the library. Consuming plugins call
 * Bootstrap::instance() (recommended on the 'plugins_loaded' action); the
 * first call wires the entire library to WordPress, every subsequent call
 * (from the same or another consuming plugin) returns the same instance and
 * binds nothing further.
 */

declare( strict_types=1 );

namespace WPMedia\MCP\OAuth;

use WP\MCP\Core\McpAdapter;
use WPMedia\MCP\OAuth\Auth\AuthorizeCallback;
use WPMedia\MCP\OAuth\Auth\AuthorizeEndpoint;
use WPMedia\MCP\OAuth\Auth\CimdResolver;
use WPMedia\MCP\OAuth\Auth\ClaudeClientVerifier;
use WPMedia\MCP\OAuth\Auth\ConsentEndpoint;
use WPMedia\MCP\OAuth\Auth\Discovery\Endpoints as DiscoveryEndpoints;
use WPMedia\MCP\OAuth\Auth\Discovery\HealthCheck as DiscoveryHealthCheck;
use WPMedia\MCP\OAuth\Auth\RevokeEndpoint;
use WPMedia\MCP\OAuth\Auth\Rewrite;
use WPMedia\MCP\OAuth\Auth\Router;
use WPMedia\MCP\OAuth\Auth\SecretManager;
use WPMedia\MCP\OAuth\Auth\TokenEndpoint;
use WPMedia\MCP\OAuth\Transport\Server;
use WPMedia\MCP\OAuth\Transport\ServerRegistrar;
use WPMedia\MCP\OAuth\Views\Render;

/**
 * Centralized single-instance bootstrap for the MCP OAuth library.
 */
final class Bootstrap {

	/**
	 * Bumped whenever any endpoint's or discovery document's rewrite regex changes.
	 */
	private const REWRITE_VERSION = '1';

	/**
	 * Option storing the rewrite-rules version last flushed for.
	 */
	private const REWRITE_OPTION = 'wpmedia_mcp_oauth_rewrite_version';

	/**
	 * The single instance.
	 *
	 * @var self
	 */
	private static self $instance;

	/**
	 * Whether register() has already run.
	 *
	 * @var bool
	 */
	private static bool $initialized = false;

	/**
	 * OAuth server context, shared across all wired collaborators.
	 *
	 * @var Context
	 */
	private Context $context;

	/**
	 * Return the single Bootstrap instance, wiring the library on first call.
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
			self::$instance->register();
		}

		return self::$instance;
	}

	/**
	 * Private constructor — use instance().
	 */
	private function __construct() {}

	/**
	 * Singletons cannot be cloned.
	 *
	 * @return void
	 */
	public function __clone() {
		_doing_it_wrong( __METHOD__, 'Bootstrap is a singleton and cannot be cloned.', '1.0.0' );
	}

	/**
	 * Singletons cannot be unserialized.
	 *
	 * @return void
	 */
	public function __wakeup() {
		_doing_it_wrong( __METHOD__, 'Bootstrap is a singleton and cannot be unserialized.', '1.0.0' );
	}

	/**
	 * Wire the object graph and bind every WordPress hook.
	 *
	 * @return void
	 */
	private function register(): void {
		if ( self::$initialized ) {
			return;
		}

		$this->context = new Context();

		$this->register_auth_router();
		$this->register_discovery( $this->context );
		$this->register_discovery_health_check( $this->context );
		$this->register_transport( $this->context );

		add_action( 'init', [ SecretManager::class, 'ensure_secret' ], 5 );
		add_action( 'init', [ $this, 'maybe_flush_rewrite_rules' ], 20 );

		// Ensure the adapter is booted so it fires mcp_adapter_init on rest_api_init@15.
		if ( class_exists( McpAdapter::class ) ) {
			McpAdapter::instance();
		}

		self::$initialized = true;
	}

	/**
	 * Wire OAuth endpoint routing.
	 *
	 * @return void
	 */
	private function register_auth_router(): void {
		$authorize = new AuthorizeEndpoint( new CimdResolver( new ClaudeClientVerifier() ) );

		$router = new Router(
			new Rewrite(),
			$authorize,
			new AuthorizeCallback( new Render() ),
			new TokenEndpoint(),
			new ConsentEndpoint(),
			new RevokeEndpoint(),
			$this->context
		);

		add_action( 'init', [ $router, 'register_rewrite_rules' ] );
		add_filter( 'query_vars', [ $router, 'add_query_vars' ] );
		add_action( 'template_redirect', [ $router, 'handle_request' ] );
		add_action( 'wp_delete_application_password', [ $router, 'purge_refresh_jti_meta' ], 10, 2 );
	}

	/**
	 * Wire the .well-known discovery documents.
	 *
	 * @param Context $context OAuth server context.
	 * @return void
	 */
	private function register_discovery( Context $context ): void {
		$discovery = new DiscoveryEndpoints( $context );

		add_action( 'init', [ $discovery, 'add_rewrite_rules' ] );
		add_filter( 'query_vars', [ $discovery, 'add_query_vars' ] );
		add_action( 'template_redirect', [ $discovery, 'handle_request' ] );
	}

	/**
	 * Wire the Site Health self-check for the .well-known discovery documents.
	 *
	 * @param Context $context OAuth server context.
	 * @return void
	 */
	private function register_discovery_health_check( Context $context ): void {
		$health_check = new DiscoveryHealthCheck( $context );

		add_filter( 'site_status_tests', [ $health_check, 'add_test' ] );
	}

	/**
	 * Wire MCP server + abilities registration.
	 *
	 * @param Context $context OAuth server context.
	 * @return void
	 */
	private function register_transport( Context $context ): void {
		$registrar = new ServerRegistrar( new Server( $context ), $context );

		add_action( 'wp_abilities_api_categories_init', [ $registrar, 'ensure_default_category' ] );
		add_action( 'wp_abilities_api_init', [ $registrar, 'ensure_shared_abilities_registered' ] );
		add_action( 'mcp_adapter_init', [ $registrar, 'register_server' ] );
	}

	/**
	 * Lazily flush rewrite rules once per REWRITE_VERSION bump.
	 *
	 * Runs after rewrite rules are (re-)registered on the same 'init' action
	 * (priority 10), so the rules exist before being persisted.
	 *
	 * @return void
	 */
	public function maybe_flush_rewrite_rules(): void {
		if ( ! $this->context->is_enabled() ) {
			return;
		}

		if ( ! $this->needs_rewrite_flush() ) {
			return;
		}

		flush_rewrite_rules( false );
		update_option( self::REWRITE_OPTION, self::REWRITE_VERSION, false );
	}

	/**
	 * Whether the OAuth rewrite rules need to be (re-)persisted.
	 *
	 * Self-heals cases the version flag alone cannot detect: a fresh site, a
	 * filter/snippet that enables the server only after init@20 on the previous
	 * load, or our rules dropped from the persisted set. When pretty permalinks
	 * are off, the rules can never be persisted, so a flag match alone is used
	 * to avoid flushing on every request.
	 *
	 * @return bool
	 */
	private function needs_rewrite_flush(): bool {
		if ( get_option( self::REWRITE_OPTION ) !== self::REWRITE_VERSION ) {
			return true;
		}

		// Plain permalinks: no pretty rules to check; a flag match is enough.
		if ( '' === (string) get_option( 'permalink_structure' ) ) {
			return false;
		}

		$rules = get_option( 'rewrite_rules' );

		return ! is_array( $rules ) || ! array_key_exists( Rewrite::AUTHORIZE_RULE, $rules );
	}

	/**
	 * Force the next 'init' to re-flush rewrite rules.
	 *
	 * Call this whenever whatever flips the `wpmedia_mcp_oauth_server_enabled`
	 * filter changes state — a version-flag match alone cannot detect that.
	 *
	 * @return void
	 */
	public static function schedule_rewrite_flush(): void {
		delete_option( self::REWRITE_OPTION );
	}
}
