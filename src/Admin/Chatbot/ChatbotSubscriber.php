<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Admin\Chatbot;

use WPMedia\BackWPup\Adapters\BackWPupAdapter;
use WPMedia\BackWPup\Admin\Chatbot\API\ChatbotRest;
use WPMedia\BackWPup\API\Rest;
use WPMedia\BackWPup\EventManagement\SubscriberInterface;

class ChatbotSubscriber implements SubscriberInterface {

	/**
	 * Adapter for BackWPup plugin data.
	 *
	 * @var BackWPupAdapter
	 */
	private BackWPupAdapter $backwpup;

	/**
	 * Chatbot instance.
	 *
	 * @var Chatbot
	 */
	private Chatbot $chatbot;

	/**
	 * Constructor.
	 *
	 * @param BackWPupAdapter $backwpup Backwpup Adapter instance.
	 * @param Chatbot         $chatbot Chatbot instance.
	 */
	public function __construct(
		BackWPupAdapter $backwpup,
		Chatbot $chatbot
	) {
		$this->chatbot  = $chatbot;
		$this->backwpup = $backwpup;
	}

	/**
	 * Returns the events this subscriber wants to listen to.
	 *
	 * @return array
	 */
	public static function get_subscribed_events(): array {
		return [
			'admin_enqueue_scripts'                => [ [ 'enqueue' ] ],
			'backwpup_admin_footer_before_version' => [ [ 'render_button' ] ],
			'admin_footer'                         => [ [ 'render_modal' ] ],
		];
	}

	/**
	 * Enqueue scripts.
	 *
	 * @param string $hook
	 * @return void
	 */
	public function enqueue( string $hook ): void {

		if ( ! $this->maybe_allow() ) {
			return;
		}

		wp_styles()->add( 'backwpup-chatbot-admin', false );
		wp_enqueue_style( 'backwpup-chatbot-admin' );
		wp_add_inline_style(
			'backwpup-chatbot-admin',
			'
		    .backwpup-chatbot-modal{display:none;position:fixed;inset:0;z-index:100000;background:rgba(0,0,0,.45);}
            .backwpup-chatbot-modal__panel{position:absolute;top:5%;left:50%;transform:translateX(-50%);width:min(1000px,92vw);height:85vh;background:#fff;border-radius:10px;box-shadow:0 10px 30px rgba(0,0,0,.2);overflow:hidden;}
            .backwpup-chatbot-modal__bar{display:flex;gap:8px;align-items:center;justify-content:space-between;padding:10px 12px;border-bottom:1px solid #eee;}
            .backwpup-chatbot-modal__iframe{width:100%;height:calc(85vh - 48px);border:0;}
            .backwpup-chatbot-modal.is-open { display:block; }
		 '
			);

		$plugin_url  = $this->backwpup->get_plugin_data( 'URL' );
		$assets_path = $plugin_url . '/assets/js/admin-chatbot.js';

		wp_register_script( 'backwpup-admin-chatbot',  $assets_path,  [],  $this->backwpup->get_plugin_data( 'Version' ),  true );
		wp_enqueue_script( 'backwpup-admin-chatbot' );

		wp_localize_script(
			'backwpup-admin-chatbot',
			'BackWPupChatbot',
			[
				'url'             => $this->chatbot->get_chatbot_url(),
				'nonce'           => wp_create_nonce( 'wp_rest' ),
				'contextEndpoint' => rest_url( Rest::ROUTE_NAMESPACE . ChatbotRest::CHATBOT_ENDPOINT ),
				'i18n'            => [
					'missingUrl' => __( 'Chatbot URL not configured yet.', 'backwpup' ),
				],
			]
		);
	}

	/**
	 * Render the button via subscribed events.
	 *
	 * @return void
	 */
	public function render_button(): void {
		if ( ! $this->maybe_allow() ) {
			return;
		}

		$this->chatbot->render_button();
	}

	/**
	 * Render the Modal via subscribed events.
	 *
	 * @return void
	 */
	public function render_modal(): void {
		if ( ! $this->maybe_allow() ) {
			return;
		}

		$this->chatbot->render_modal();
	}

	/**
	 * Maybe allow.
	 *
	 * @return bool
	 */
	private function maybe_allow(): bool {
		$screen    = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$screen_id = ( $screen && isset( $screen->id ) ) ? (string) $screen->id : '';

		if ( '' === $screen_id || false === strpos( $screen_id, 'backwpup' ) ) {
			return false;
		}

		return true;
	}
}
