<?php # -*- coding: utf-8 -*-

namespace Inpsyde\BackWPup\Notice;

use Inpsyde\EnvironmentChecker\Constraints;

/**
 * Class PhpNotice
 *
 * @package Inpsyde\BackWPup\Notice
 */
class PhpNotice extends EnvironmentNotice
{

    const OPTION_NAME = 'backwpup_notice_php_version';
    const ID = self::OPTION_NAME;

    /**
     * {@inheritdoc}
     */
    protected function getConstraints()
    {
        return [
            new Constraints\PhpConstraint('7.2'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function message()
    {
        return new NoticeMessage('php');
    }
}
