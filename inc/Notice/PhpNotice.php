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
    protected function get_constraints()
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
        return new NoticeMessage([
            'content' => [
                __("BackWPup is dropping support for PHP versions less than 7.2. As such, using outdated and unsupported versions of PHP may expose your site to security vulnerabilities. Please update PHP to the latest version. Ask your hoster if you don't know how.", 'backwpup'),
                __("For further information <a href=\"https://backwpup.com/docs/php-7-2-update/\" target=\"_blank\">see here</a>, and if any questions remain contact our support team.", 'backwpup'),
            ],
        ]);
    }
}
