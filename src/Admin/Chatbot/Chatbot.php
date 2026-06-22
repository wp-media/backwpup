<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Admin\Chatbot;

use WPMedia\BackWPup\Common\AbstractRender;

class Chatbot extends AbstractRender {

	public const CHATBOT_URL = 'https://backwpup.com/support-redirect/chat/';

	/**
	 * Context Snapshot Builder instance.
	 *
	 * @var ContextSnapshotBuilder Instance
	 */
	private ContextSnapshotBuilder $snapshot_builder;

	/**
	 * Constructor.
	 *
	 * @param string                 $template_path Template path.
	 * @param ContextSnapshotBuilder $snapshot_builder Snapshot builder.
	 */
	public function __construct(
		string $template_path,
		ContextSnapshotBuilder $snapshot_builder
	) {
		parent::__construct( $template_path );
		$this->snapshot_builder = $snapshot_builder;
	}

	/**
	 * The chatbot URL.
	 *
	 * @return string
	 */
	public function get_chatbot_url(): string {
		$chatbot_url = wpm_apply_filters_typesafe( 'backwpup_chatbot_url', self::CHATBOT_URL );
		return (string) wpm_apply_filters_typesafe( 'backwpup_url_add_hash', $chatbot_url );
	}

	/**
	 * Render the button.
	 *
	 * @return void
	 */
	public function render_button(): void {
		$data = [
			'label' => __( 'Get Help', 'backwpup' ),
		];
		/**
		 * Fires when the contact support button is displayed.
		 */
		do_action( 'backwpup_track_support_tool_button_displayed' );
		echo $this->generate( 'button', $data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Render the modal.
	 *
	 * @return void
	 */
	public function render_modal(): void {
		$data = [
			'title'    => __( 'Get Help', 'backwpup' ),
			'close'    => __( 'Close', 'backwpup' ),
			'snapshot' => $this->snapshot_builder->build( 5 ),
		];

		echo $this->generate( 'modal', $data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
