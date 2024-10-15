<?php

namespace Inpsyde\BackWPup\Notice; // phpcs:ignore

use BackWPup_Option;
use BackWPup;

class RestoreFeatureInformationNotice extends Notice {
	/**
	 * The option name.
	 *
	 * @var string
	 */
	public const OPTION_NAME = 'restore_feature_information_notice';
	/**
	 * The notice ID.
	 *
	 * @var string
	 */
	public const ID = self::OPTION_NAME;

	/**
	 * {@inheritdoc}
	 *
	 * @param NoticeMessage $message The message to render.
	 */
	protected function render( NoticeMessage $message ): void {
		$this->view->info( $message, null );
	}

	/**
	 * {@inheritdoc}
	 */
	protected function isScreenAllowed(): bool {
		return true;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return NoticeMessage
	 */
	protected function message(): NoticeMessage {
		$notice_message             = new NoticeMessage(
			'restore_feature_information',
			'Leave us a review !',
			'https://wordpress.org/support/plugin/backwpup/reviews/?rate=5#new-post'
		);
		$notice_message->dismissurl = DismissibleNoticeOption::dismiss_action_url(
			static::ID,
			DismissibleNoticeOption::FOR_GOOD_ACTION
		);
		return $notice_message;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function shouldDisplay(): bool {
		// Check if the notice has been dismissed.
		if ( parent::shouldDisplay() ) {
			// Must only be displayed in the free version.
			return ! \BackWPup::is_pro();
		}
		return false;
	}
}
