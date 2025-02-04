<?php

namespace Inpsyde\BackWPup\Notice; // phpcs:ignore

use BackWPup_Option;
use BackWPup;

class NewUINotice extends Notice {
	/**
	 * The option name.
	 *
	 * @var string
	 */
	public const OPTION_NAME = 'new_ui_notice';
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
			'newUI'
			);
		$notice_message->dismissurl = DismissibleNoticeOption::dismiss_action_url(
			static::ID,
			DismissibleNoticeOption::FOR_GOOD_ACTION
		);
		return $notice_message;
	}
}
