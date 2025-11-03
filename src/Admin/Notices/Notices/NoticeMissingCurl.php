<?php

namespace WPMedia\BackWPup\Admin\Notices\Notices;

use Inpsyde\BackWPup\Notice\NoticeMessage;
use Inpsyde\BackWPup\Notice\DismissibleNoticeOption;
use Inpsyde\BackWPup\Notice\NoticeView;
use WPMedia\BackWPup\Adapters\JobAdapter;

/**
 * Notice When jobs data is corrupted
 */
class NoticeMissingCurl extends AbstractNotice {

	/**
	 * The unique ID for this notice.
	 */
	public const ID = 'notice_missing_curl';


	/**
	 * Constructor.
	 *
	 * @param NoticeView $view The view renderer for the notice.
	 */
	public function __construct( NoticeView $view ) {
		parent::__construct( $view, true );
	}

	/**
	 * Determine if the notice should be displayed.
	 *
	 * @return bool
	 */
	protected function should_display(): bool {
		if ( ! parent::should_display() ) {
			return false;
		}

		return ! function_exists( 'curl_init' );
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
	 * Get the dismiss action URL for the notice.
	 *
	 * @return string|null
	 */
	protected function get_dismiss_action_url(): ?string {
		if ( ! $this->dismissible ) {
			return null;
		}

		return DismissibleNoticeOption::dismiss_action_url(
			self::ID,
			DismissibleNoticeOption::FOR_NOW_ACTION
		);
	}

	/**
	 * Build the message for the notice.
	 *
	 * @return NoticeMessage
	 */
	protected function message(): NoticeMessage {
		$notice_message             = new NoticeMessage( self::ID );
		$notice_message->dismissurl = $this->get_dismiss_action_url();
		return $notice_message;
	}

	/**
	 * Check if the current screen is allowed for this notice.
	 *
	 * @return bool
	 */
	protected function is_screen_allowed(): bool {
		$screen = get_current_screen();

		// Check if the current screen is a BackWPup page.
		return isset( $screen->id ) && str_contains( $screen->id, 'backwpup' );
	}
}
