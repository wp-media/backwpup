<?php # -*- coding: utf-8 -*-

namespace Inpsyde\BackWPup\Notice;

use Inpsyde\EnvironmentChecker\Constraints;

/**
 * Class WordPressNotice
 *
 * @package Inpsyde\BackWPup\Notice
 */
class WordPressNotice extends EnvironmentNotice
{

    const OPTION_NAME = 'backwpup_notice_wordpress_version';
    const ID = self::OPTION_NAME;

    /**
     * {@inheritdoc}
     */
    protected function getConstraints()
    {
        return [
            new Constraints\WordPressConstraint('5.0'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function message()
    {
        return new NoticeMessage('wordpress');
    }
}
