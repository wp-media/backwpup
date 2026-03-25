<?php

namespace Inpsyde\BackWPup\Notice;

use Inpsyde\EnvironmentChecker\Constraints\WordPressConstraint;

class WordPressNotice extends EnvironmentNotice {

	/**
	 * Option name for the WordPress version notice.
	 *
	 * @var string
	 */
	public const OPTION_NAME = 'backwpup_notice_wordpress_version';
	/**
	 * Notice identifier for the WordPress version notice.
	 *
	 * @var string
	 */
	public const ID = self::OPTION_NAME;

	/**
	 * {@inheritdoc}
	 */
	protected function get_constraints(): array {
		return [
			new WordPressConstraint( '5.0' ),
		];
	}

	/**
	 * {@inheritdoc}
	 */
	protected function message(): NoticeMessage {
		return new NoticeMessage( 'WordPress' );
	}
}
