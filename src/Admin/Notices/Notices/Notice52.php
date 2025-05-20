<?php

namespace WPMedia\BackWPup\Admin\Notices\Notices;

use Inpsyde\BackWPup\Notice\NoticeMessage;
use Inpsyde\BackWPup\Notice\DismissibleNoticeOption;
use Inpsyde\BackWPup\Notice\NoticeView;
use WPMedia\BackWPup\Adapters\BackWPupAdapter;

/**
 * Notice after update to 5.2.
 */
class Notice52 extends AbstractNotice {

	/**
	 * The unique ID for this notice.
	 */
	public const ID = 'notice_5_2';

	/**
	 * Adapter for BackWPup plugin data.
	 *
	 * @var BackWPupAdapter
	 */
	private BackWPupAdapter $backwpup;

	/**
	 * Constructor.
	 *
	 * @param NoticeView      $view The view renderer for the notice.
	 * @param BackWPupAdapter $backwpup Adapter for plugin data.
	 */
	public function __construct( NoticeView $view, BackWPupAdapter $backwpup ) {
		parent::__construct( $view, true );
		$this->backwpup = $backwpup;
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

		$new_version = $this->backwpup->get_plugin_data( 'Version' );

		// We will show this notice only with version 5.2 and before 5.2.2.
		return version_compare( $new_version, '5.2', '>=' ) && version_compare( $new_version, '5.2.2', '<' );
	}

	/**
	 * Render the notice using the view.
	 *
	 * @param NoticeMessage $message
	 */
	protected function render( NoticeMessage $message ): void {
		$this->view->info( $message, null );
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
			DismissibleNoticeOption::FOR_USER_FOR_GOOD_ACTION
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
