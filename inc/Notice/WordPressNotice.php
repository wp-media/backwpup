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
    protected function get_constraints()
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
        return new NoticeMessage([
            'content' => [
                __('BackWPup is dropping support for WordPress versions less than 5.0. Please update WordPress to the latest version. Without an update, you will not receive any new features.', 'backwpup'),
                __('<a href="https://backwpup.com/support/" target="_blank">Contact our support team here</a> if any questions remain.', 'backwpup'),
            ],
        ]);
    }
}
