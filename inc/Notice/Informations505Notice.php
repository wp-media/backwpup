<?php

namespace Inpsyde\BackWPup\Notice; // phpcs:ignore

use BackWPup_Option;
use BackWPup;

class Informations505Notice extends Notice {
	/**
	 * The option name.
	 *
	 * @var string
	 */
	public const OPTION_NAME = 'informations_505_notice';
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
		$activation_time = get_site_option( 'backwpup_activation_time' );
		$activation_date = new \DateTime( '@' . $activation_time );
		$cutoff_date     = new \DateTime( '2025-02-05' );
		$message_content = 'newUsersInformations505';
		if ( $activation_date < $cutoff_date ) {
			$message_content = 'existingUsersInformations505';
		}

		$notice_message             = new NoticeMessage(
			$message_content
			);
		$notice_message->dismissurl = DismissibleNoticeOption::dismiss_action_url(
			static::ID,
			DismissibleNoticeOption::FOR_GOOD_ACTION
		);
		return $notice_message;
	}
}
