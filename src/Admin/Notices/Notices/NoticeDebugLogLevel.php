<?php

namespace WPMedia\BackWPup\Admin\Notices\Notices;

use Inpsyde\BackWPup\Notice\NoticeMessage;
use Inpsyde\BackWPup\Notice\NoticeView;

/**
 * Notice about debug logging being enabled while debug logs have been generated.
 */
class NoticeDebugLogLevel extends AbstractNotice {

	/**
	 * The unique ID for this notice.
	 */
	public const ID = 'notice_debug_log_level';

	/**
	 * Constructor.
	 *
	 * @param NoticeView $view The view renderer for the notice.
	 */
	public function __construct( NoticeView $view ) {
		parent::__construct( $view, false );
	}

	/**
	 * Determine if the notice should be displayed.
	 *
	 * @return bool
	 */
	protected function should_display(): bool {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			return false;
		}

		$log_level = get_site_option( 'backwpup_cfg_loglevel', 'normal' );
		$count     = (int) get_site_option( 'backwpup_debug_log_count', 0 );
		return strpos( $log_level, 'debug' ) !== false && 0 < $count;
	}

	/**
	 * Render the notice using the view.
	 *
	 * @param NoticeMessage $message
	 */
	protected function render( NoticeMessage $message ): void {
		$this->view->warning( $message, null );
	}

	/**
	 * Build the message for the notice.
	 *
	 * @return NoticeMessage
	 */
	protected function message(): NoticeMessage {
		return new NoticeMessage( self::ID );
	}

	/**
	 * Check if the current screen is allowed for this notice.
	 *
	 * @return bool
	 */
	protected function is_screen_allowed(): bool {
		$screen = get_current_screen();

		return isset( $screen->id ) && strpos( $screen->id, 'backwpup' ) !== false;
	}
}
