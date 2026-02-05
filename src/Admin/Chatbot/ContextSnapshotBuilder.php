<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Admin\Chatbot;

use WPMedia\BackWPup\Adapters\BackWPupAdapter;
use WPMedia\BackWPup\Common\ErrorSignals\ErrorSignalsStore;
use WPMedia\Mixpanel\Optin;

class ContextSnapshotBuilder {

	/**
	 * Adapter instance.
	 *
	 * @var BackWPupAdapter Instance.
	 */
	private BackWPupAdapter $backwpup_adapter;

	/**
	 * Error Signal Store instance.
	 *
	 * @var ErrorSignalsStore Instance.
	 */
	private ErrorSignalsStore $signals_store;

	/**
	 * Optin instance.
	 *
	 * @var Optin
	 */
	private $optin;

	/**
	 * Constructor.
	 *
	 * @param BackWPupAdapter   $backwpup_adapter Instance.
	 * @param ErrorSignalsStore $signals_store Instance.
	 * @param Optin             $optin Optin instance.
	 */
	public function __construct(
		BackWPupAdapter $backwpup_adapter,
		ErrorSignalsStore $signals_store,
		Optin $optin
	) {
		$this->backwpup_adapter = $backwpup_adapter;
		$this->signals_store    = $signals_store;
		$this->optin            = $optin;
	}

	/**
	 * Build context snapshot.
	 *
	 * @param int $signals_limit limit.
	 * @return array
	 */
	public function build( int $signals_limit = 20 ): array {
		$active_plugins = (array) get_option( 'active_plugins', [] );
		$theme          = wp_get_theme();
		return [
			'backwpup_version'        => (string) $this->backwpup_adapter->get_plugin_data( 'Version' ),
			'wp_version'              => (string) get_bloginfo( 'version' ),
			'domain'                  => (string) wp_parse_url( get_site_url(), PHP_URL_HOST ),
			'php_version'             => PHP_VERSION,
			'active_theme'            => [
				'name'       => $theme->get( 'Name' ),
				'version'    => $theme->get( 'Version' ),
				'stylesheet' => $theme->get_stylesheet(),
			],
			'active_plugins'          => $active_plugins,
			'recent_error_signals'    => $this->signals_store->latest( $signals_limit ),
			'backwpup_mixpanel_optin' => $this->optin->is_enabled(),
			'generated_at'            => time(),
		];
	}
}
