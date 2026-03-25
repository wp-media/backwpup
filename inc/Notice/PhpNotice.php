<?php

namespace Inpsyde\BackWPup\Notice;

use Inpsyde\EnvironmentChecker\Constraints\PhpConstraint;

class PhpNotice extends EnvironmentNotice {

	/**
	 * Option name for the PHP version notice.
	 *
	 * @var string
	 */
	public const OPTION_NAME = 'backwpup_notice_php_version';
	/**
	 * Notice identifier for the PHP version notice.
	 *
	 * @var string
	 */
	public const ID = self::OPTION_NAME;

	/**
	 * {@inheritdoc}
	 */
	protected function get_constraints(): array {
		return [
			new PhpConstraint( '7.4' ),
		];
	}

	/**
	 * {@inheritdoc}
	 */
	protected function message(): NoticeMessage {
		return new NoticeMessage( 'php' );
	}
}
