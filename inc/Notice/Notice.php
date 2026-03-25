<?php

namespace Inpsyde\BackWPup\Notice;

use BackWPup;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class Notice {

	/**
	 * Notice identifier.
	 *
	 * @var string
	 */
	public const ID = 'notice';
	/**
	 * Required capability to view the notice.
	 *
	 * @var string
	 */
	public const CAPABILITY = 'backwpup';

	/**
	 * Admin notice type.
	 *
	 * @var string
	 */
	public const TYPE_ADMIN = 'admin';
	/**
	 * BackWPup notice type.
	 *
	 * @var string
	 */
	public const TYPE_BACKWPUP = 'backwpup';

	/**
	 * Main admin screen ID.
	 *
	 * @var string
	 */
	private const MAIN_ADMIN_PAGE_ID = 'toplevel_page_backwpup';
	/**
	 * Network admin screen ID.
	 *
	 * @var string
	 */
	private const NETWORK_ADMIN_PAGE_ID = 'toplevel_page_backwpup-network';

	/**
	 * BackWPup admin screen IDs.
	 *
	 * @var string[]
	 */
	protected static $main_admin_page_ids = [
		self::MAIN_ADMIN_PAGE_ID,
		self::NETWORK_ADMIN_PAGE_ID,
	];

	/**
	 * Notice view instance.
	 *
	 * @var NoticeView
	 */
	protected $view;

	/**
	 * Whether this notice should be dismissible.
	 *
	 * @var bool
	 */
	protected $dismissible = false;

	/**
	 * Creates a notice instance.
	 *
	 * @param NoticeView $view        Notice view instance.
	 * @param bool       $dismissible Whether the notice is dismissible.
	 */
	public function __construct( NoticeView $view, bool $dismissible = true ) {
		$this->view        = $view;
		$this->dismissible = $dismissible;
	}

	/**
	 * Initialize.
	 *
	 * @param string $type The notice type, either Notice::TYPE_ADMIN or Notice::TYPE_BACKWPUP.
	 *                     Notice::TYPE_BACKWPUP makes the notice only visible on BackWPup pages.
	 *                     Notice::TYPE_ADMIN makes the notice available on all WP admin pages.
	 *
	 * @throws \InvalidArgumentException When an invalid notice type is provided.
	 */
	public function init( string $type = self::TYPE_BACKWPUP ): void {
		if ( ! is_admin() ) {
			return;
		}
		if ( ! current_user_can( static::CAPABILITY ) ) {
			return;
		}
		if ( self::TYPE_BACKWPUP === $type ) {
			add_action(
				'backwpup_admin_messages',
				function (): void {
					$this->notice();
				},
				20
				);
		} elseif ( static::TYPE_ADMIN === $type ) {
			$action_name = is_multisite() ? 'network_admin_notices' : 'admin_notices';
			add_action(
				$action_name,
				function (): void {
					$this->notice();
				},
				20
				);
		} else {
			throw new \InvalidArgumentException(
				esc_html__( 'Invalid notice type specified', 'backwpup' )
			);
		}

		if ( $this->dismissible ) {
			add_action(
				'admin_enqueue_scripts',
				function (): void {
					$this->enqueue_scripts();
				}
			);
			DismissibleNoticeOption::setup_actions( true, static::ID, static::CAPABILITY );
		}
	}

	/**
	 * Enqueue Scripts.
	 */
	public function enqueue_scripts(): void {
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_enqueue_script(
			'backwpup-notice',
			untrailingslashit( BackWPup::get_plugin_data( 'URL' ) ) . sprintf( '/assets/js/notice%s.js', $suffix ),
			[ 'underscore', 'jquery' ],
			BackWPup::get_plugin_data( 'Version' ),
			true
		);
	}

	/**
	 * Print Notice.
	 */
	public function notice(): void {
		if ( ! $this->is_screen_allowed() ) {
			return;
		}
		if ( ! $this->should_display() ) {
			return;
		}

		$this->render( $this->message() );
	}

	/**
	 * Render the notice with the appropriate view type.
	 *
	 * This method can specify whether the notice should be a success, error,
	 * warning, info, or generic notice.
	 *
	 * @param NoticeMessage $message The message to render.
	 */
	protected function render( NoticeMessage $message ): void {
		$this->view->notice( $message, $this->get_dismiss_action_url() );
	}

	/**
	 * Gets the dismissible action URL from DismissibleNoticeOption.
	 *
	 * @return string|null The URL to dismiss the notice.
	 */
	protected function get_dismiss_action_url(): ?string {
		if ( $this->dismissible ) {
			return DismissibleNoticeOption::dismiss_action_url(
				static::ID,
				DismissibleNoticeOption::FOR_USER_FOR_GOOD_ACTION
			);
		}

		return null;
	}

	/**
	 * Return the message to display in the notice.
	 *
	 * @return NoticeMessage The message to display.
	 */
	abstract protected function message(): NoticeMessage;

	/**
	 * Returns whether the current screen should show the notice.
	 */
	protected function is_screen_allowed(): bool {
		$screen = get_current_screen();
		if ( ! $screen instanceof \WP_Screen ) {
			return false;
		}

		$screen_id = $screen->id;

		return in_array( $screen_id, static::$main_admin_page_ids, true );
	}

	/**
	 * Determines whether to display the notice.
	 */
	protected function should_display(): bool {
		if ( $this->dismissible ) {
			$option = new DismissibleNoticeOption( true );

			return ! $option->is_dismissed( static::ID );
		}

		return true;
	}
}
