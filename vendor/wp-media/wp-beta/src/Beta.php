<?php
declare(strict_types=1);

namespace WPMedia\Beta;

class Beta {
	/**
	 * The opt-in instance.
	 *
	 * @var Optin
	 */
	private $optin;

	/**
	 * The plugin file.
	 *
	 * @var string
	 */
	private static $file = '';

	/**
	 * The plugin slug.
	 *
	 * @var string
	 */
	private $plugin_slug;

	/**
	 * The trunk version fetched from the SVN.
	 *
	 * @var string
	 */
	private $trunk_version = '0';

	/**
	 * The current version of the plugin.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * The update message.
	 *
	 * @var string
	 */
	private $update_message = 'This update will install a beta version of the plugin.';

	/**
	 * Constructor.
	 *
	 * @param Optin  $optin       The opt-in instance.
	 * @param string $file        The plugin file.
	 * @param string $plugin_slug The plugin slug.
	 * @param string $version     The current version of the plugin.
	 */
	public function __construct( Optin $optin, string $file, string $plugin_slug, string $version ) {
		$this->optin       = $optin;
		self::$file        = $file;
		$this->plugin_slug = $plugin_slug;
		$this->version     = $version;
	}

	/**
	 * Registers the hooks for the beta functionality.
	 *
	 * @return void
	 */
	public function init(): void {
		add_filter( 'site_transient_update_plugins', [ $this, 'transient_update_plugins' ] );
		add_action( 'in_plugin_update_message-' . self::$file, [ $this, 'plugin_update_message' ] );
	}

	/**
	 * Sets the update message.
	 *
	 * @param string $message The update message.
	 *
	 * @return void
	 */
	public function set_update_message( string $message ): void {
		$this->update_message = $message;
	}

	/**
	 * Add the beta version to the update transient.
	 *
	 * @param \stdClass $transient The transient object.
	 *
	 * @return \stdClass
	 */
	public function transient_update_plugins( $transient ) {
		if ( ! $this->optin->is_enabled() ) {
			return $transient;
		}

		$beta_version = $this->get_latest_beta_version();
		$new_version  = isset( $transient->response[ self::$file ] ) && ! empty( $transient->response[ self::$file ]->new_version ) ? $transient->response[ self::$file ]->new_version : '0';

		if ( ! $beta_version ) {
			return $transient;
		}

		if ( version_compare(
			$beta_version,
			$this->version,
			'>'
		) && version_compare( $beta_version, $new_version, '>' ) ) {
			$transient = $this->inject_beta( $transient, $beta_version );
		}

		return $transient;
	}

	/**
	 * Get latest beta version available.
	 *
	 * @return string Latest beta version number.
	 */
	private function get_latest_beta_version() {
		$version = get_transient( $this->plugin_slug . '_trunk_version' );

		if (
			false === $version
			||
			! is_string( $version )
		) {
			$version = $this->fetch_trunk_version();
		}

		$beta = '0';

		if ( str_contains( $version, 'beta' ) ) {
			$beta = $version;
		}

		return $beta;
	}

	/**
	 * Fetch latest plugin file from public SVN and get version number.
	 *
	 * @return string
	 */
	private function fetch_trunk_version(): string {
		if ( '0' !== $this->trunk_version ) {
			return $this->trunk_version;
		}

		$response = wp_remote_get( 'https://plugins.svn.wordpress.org/' . $this->plugin_slug . '/trunk/' . $this->plugin_slug . '.php' );

		if ( is_wp_error( $response ) ) {
			return $this->trunk_version;
		}

		$plugin_file = wp_remote_retrieve_body( $response );

		preg_match( '/Version:\s+([0-9a-zA-Z.-]+)\s*$/m', $plugin_file, $matches );

		if ( empty( $matches[1] ) ) {
			return $this->trunk_version;
		}

		$this->trunk_version = $matches[1];

		set_transient( $this->plugin_slug . '_trunk_version', $this->trunk_version, ( 12 * HOUR_IN_SECONDS ) );

		return $this->trunk_version;
	}

	/**
	 * Inject beta version into the update transient.
	 *
	 * @param \stdClass|false $value        The transient value.
	 * @param string          $beta_version The beta version number.
	 *
	 * @return \stdClass
	 */
	private function inject_beta( $value, $beta_version ) {
		if ( empty( $value ) ) {
			$value = new \stdClass();
		}

		if ( empty( $value->response ) ) {
			$value->response = [];
		}

		$value->response[ self::$file ] = new \stdClass();

		$plugin_data = $this->get_plugin_data( $beta_version, 'https://downloads.wordpress.org/plugin/' . $this->plugin_slug . '.zip' );

		foreach ( $plugin_data as $prop_key => $prop_value ) {
			$value->response[ self::$file ]->{$prop_key} = $prop_value;
		}

		$value->response[ self::$file ]->is_beta        = true;
		$value->response[ self::$file ]->upgrade_notice = $this->update_message;

		if ( empty( $value->no_update ) ) {
			$value->no_update = [];
		}

		unset( $value->no_update[ self::$file ] );

		return $value;
	}

	/**
	 * Get plugin data for the update.
	 *
	 * @param string $version The version number.
	 * @param string $package The package URL.
	 *
	 * @return array<string, array<string, string>|string>
	 */
	private function get_plugin_data( $version, $package ): array {
		return [
			'id'          => 'w.org/plugins/' . $this->plugin_slug,
			'slug'        => $this->plugin_slug,
			'plugin'      => self::$file,
			'new_version' => $version,
			'url'         => 'https://wordpress.org/plugins/' . $this->plugin_slug . '/',
			'package'     => $package,
			'icons'       =>
			[
				'2x'  => 'https://ps.w.org/' . $this->plugin_slug . '/assets/icon-256x256.png',
				'1x'  => 'https://ps.w.org/' . $this->plugin_slug . '/assets/icon.png',
				'svg' => 'https://ps.w.org/' . $this->plugin_slug . '/assets/icon.svg',
			],
			'banners'     =>
			[
				'2x' => 'https://ps.w.org/' . $this->plugin_slug . '/assets/banner-1544x500.jpg',
				'1x' => 'https://ps.w.org/' . $this->plugin_slug . '/assets/banner-772x250.jpg',
			],
			'banners_rtl' => [],
		];
	}

	/**
	 * Display a message in the plugin update list.
	 *
	 * @param string[] $plugin_data The plugin data.
	 *
	 * @return void
	 */
	public function plugin_update_message( $plugin_data ): void {
		if ( ! $this->optin->is_enabled() ) {
			return;
		}

		if ( empty( $plugin_data['is_beta'] ) ) {
			return;
		}

		printf(
			'&nbsp;<em>%s</em>',
			esc_html( $this->update_message )
		);
	}
}
