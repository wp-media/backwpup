<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Admin\Chatbot;

use WPMedia\BackWPup\Common\AbstractRender;

class Chatbot extends AbstractRender {

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
		return (string) wpm_apply_filters_typesafe( 'backwpup_chatbot_url', 'https://backwpup.com/support-redirect/chat/' );
	}

	/**
	 * Render the button.
	 *
	 * @return void
	 */
	public function render_button(): void {
		$data = [
			'label' => __( 'Contact Support', 'backwpup' ),
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
			'title'    => __( 'Contact Support', 'backwpup' ),
			'close'    => __( 'Close', 'backwpup' ),
			'snapshot' => $this->snapshot_builder->build( 5 ),
		];

		echo $this->generate( 'modal', $data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
