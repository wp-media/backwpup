<?php

namespace Inpsyde\BackWPup\Notice;

use BackWPup_Option;

class DropboxNotice extends Notice {

	/**
	 * Option name for the Dropbox reauthentication notice.
	 *
	 * @var string
	 */
	public const OPTION_NAME = 'backwpup_notice_dropbox_needs_reauthenticated';
	/**
	 * Notice identifier for the Dropbox reauthentication notice.
	 *
	 * @var string
	 */
	public const ID = self::OPTION_NAME;

	/**
	 * List of jobs that need to be reauthenticated.
	 *
	 * @var array<int, string>
	 */
	private $jobs = [];

	/**
	 * Renders the notice as a warning.
	 *
	 * {@inheritdoc}
	 *
	 * @param NoticeMessage $message The message to render.
	 */
	protected function render( NoticeMessage $message ): void {
		$this->view->warning( $message, $this->get_dismiss_action_url() );
	}

	/**
	 * {@inheritdoc}
	 */
	protected function is_screen_allowed(): bool {
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function should_display(): bool {
		if ( ! parent::should_display() ) {
			return false;
		}

		$jobs = BackWPup_Option::get_job_ids();

		foreach ( $jobs as $job ) {
			$token = BackWPup_Option::get( $job, 'dropboxtoken' );
			if ( is_array( $token ) && isset( $token['access_token'] ) && ! isset( $token['refresh_token'] ) ) {
				$name = BackWPup_Option::get( $job, 'name' );
				if ( is_string( $name ) ) {
					$this->jobs[ $job ] = $name;
				}
			}
		}

		return ! empty( $this->jobs );
	}

	/**
	 * Builds the notice message.
	 *
	 * @return NoticeMessage The notice message.
	 */
	protected function message(): NoticeMessage {
		$message       = new NoticeMessage( 'dropbox' );
		$message->jobs = $this->jobs;

		return $message;
	}
}
