<?php

namespace WPMedia\BackWPup\Admin\Notices\Notices;

use Inpsyde\BackWPup\Notice\NoticeMessage;
use Inpsyde\BackWPup\Notice\DismissibleNoticeOption;
use Inpsyde\BackWPup\Notice\NoticeView;
use WPMedia\BackWPup\Adapters\JobAdapter;

/**
 * Notice When jobs data is corrupted
 */
class NoticeDataCorrupted extends AbstractNotice {

	/**
	 * The unique ID for this notice.
	 */
	public const ID = 'notice_data_corrupted';

	/**
	 * Adapter for job data.
	 *
	 * @var JobAdapter
	 */
	private JobAdapter $jobs_adapter;

	/**
	 * Constructor.
	 *
	 * @param NoticeView $view The view renderer for the notice.
	 * @param JobAdapter $jobs_adapter Adapter for job data.
	 */
	public function __construct( NoticeView $view, JobAdapter $jobs_adapter ) {
		parent::__construct( $view, true );
		$this->jobs_adapter = $jobs_adapter;
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

		try {
			$jobs = $this->jobs_adapter->get_jobs();
			if ( ! is_array( $jobs ) ) {
				return true;
			}
		} catch ( \Exception $e ) {
			return true;
		}
		return false;
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
