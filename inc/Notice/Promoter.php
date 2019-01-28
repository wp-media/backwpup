<?php # -*- coding: utf-8 -*-

namespace Inpsyde\BackWPup\Notice;

/**
 * Class Promoter
 *
 * @package Inpsyde\BackWPup\Notice
 */
class Promoter {

	const CAPABILITY = 'backwpup';
	const MAIN_ADMIN_PAGE_IDS = 'toplevel_page_backwpup';
	const NETWORK_ADMIN_PAGE_ID = 'toplevel_page_backwpup-network';
	const OPTION_NAME = 'backwpup_notice_promoter';
	const ID = self::OPTION_NAME;
	const DEFAULT_LANGUAGE = 'en';

	/**
	 * @var array
	 */
	private static $main_admin_page_ids = array(
		self::MAIN_ADMIN_PAGE_IDS,
		self::NETWORK_ADMIN_PAGE_ID,
	);

	/**
	 * @var \Inpsyde\BackWPup\Notice\PromoterUpdater
	 */
	private $updater;

	/**
	 * @var \Inpsyde\BackWPup\Notice\PromoterView
	 */
	private $view;

	/**
	 * Promoter constructor
	 *
	 * @param \Inpsyde\BackWPup\Notice\PromoterUpdater $updater
	 * @param \Inpsyde\BackWPup\Notice\PromoterView $view
	 */
	public function __construct( PromoterUpdater $updater, PromoterView $view ) {

		$this->updater = $updater;
		$this->view = $view;
	}

	/**
	 * Initialize
	 */
	public function init() {

		if ( ! is_admin() || ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		add_action( 'backwpup_admin_messages', array( $this, 'notice' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		DismissibleNoticeOption::setup_actions( true, self::ID, self::CAPABILITY );
	}

	/**
	 * Enqueue Scripts
	 */
	public function enqueue_scripts() {

		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_enqueue_script(
			'backwpup-notice-promoter',
			untrailingslashit( \BackWPup::get_plugin_data( 'URL' ) ) . "/assets/js/notice{$suffix}.js",
			array( 'underscore', 'jquery' ),
			filemtime( untrailingslashit( \BackWPup::get_plugin_data( 'plugindir' ) . "/assets/js/notice{$suffix}.js" ) ),
			true
		);
	}

	/**
	 * Print Notice
	 */
	public function notice() {

		$screen_id = get_current_screen()->id;
		if ( ! in_array( $screen_id, self::$main_admin_page_ids, true ) || ! $this->should_display() ) {
			return;
		}

		$message = $this->message();
		if ( ! $message->content() ) {
			return;
		}

		$dismiss_action_url = DismissibleNoticeOption::dismiss_action_url(
			Promoter::ID,
			DismissibleNoticeOption::FOR_USER_FOR_GOOD_ACTION
		);

		$this->view->notice( $message, $dismiss_action_url );
	}

	/**
	 * @return bool|string
	 */
	private function locale_code() {

		return substr( get_locale(), 0, 2 );
	}

	/**
	 * @return \Inpsyde\BackWPup\Notice\PromoterMessage
	 */
	private function message() {

		$language = self::DEFAULT_LANGUAGE;
		$locale_code = $this->locale_code();

		$data_message = is_multisite()
			? get_site_transient( self::OPTION_NAME )
			: get_transient( self::OPTION_NAME );

		if ( false === $data_message ) {
			$data_message = $this->updater->update();
		}

		if ( isset( $data_message[ $locale_code ] ) ) {
			$language = $locale_code;
		}

		$data_message = isset( $data_message[ $language ] ) ? $data_message[ $language ] : array();

		return new PromoterMessage( $data_message );
	}

	/**
	 * @return bool
	 */
	private function should_display() {

		$option = new DismissibleNoticeOption( true );

		return false === (bool) $option->is_dismissed( self::ID );
	}
}
