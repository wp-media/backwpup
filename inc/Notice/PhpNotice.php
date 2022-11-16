<?php

namespace Inpsyde\BackWPup\Notice;

use Inpsyde\EnvironmentChecker\Constraints\PhpConstraint;

class PhpNotice extends EnvironmentNotice
{
    /**
     * @var string
     */
    public const OPTION_NAME = 'backwpup_notice_php_version';
    /**
     * @var string
     */
    public const ID = self::OPTION_NAME;

    /**
     * {@inheritdoc}
     */
    protected function getConstraints(): array
    {
        return [
            new PhpConstraint('7.2'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function message(): NoticeMessage
    {
        return new NoticeMessage('php');
    }
}
