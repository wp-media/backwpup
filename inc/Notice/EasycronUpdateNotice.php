<?php

namespace Inpsyde\BackWPup\Notice; // phpcs:ignore

use BackWPup_Option;
use BackWPup;

class EasycronUpdateNotice extends Notice {
	/**
	 * The option name.
	 *
	 * @var string
	 */
	public const OPTION_NAME = 'easycron_update_notice';
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
		$this->view->info( $message, $this->getDismissActionUrl() );
	}

	/**
	 * {@inheritdoc}
	 */
	protected function isScreenAllowed(): bool {
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function shouldDisplay(): bool {
		// Check if the notice has been dismissed.
		if ( parent::shouldDisplay() ) {
			return get_site_option( 'backwpup_easycron_update', false );
		}
		return false;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return NoticeMessage
	 */
	protected function message(): NoticeMessage {

		return new NoticeMessage( 'easycron_update' );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return string
	 */
	protected function getDismissActionUrl(): ?string {
		if ( $this->dismissible ) {
			return DismissibleNoticeOption::dismiss_action_url(
				static::ID,
				DismissibleNoticeOption::FOR_GOOD_ACTION
			);
		}

		return null;
	}
}
