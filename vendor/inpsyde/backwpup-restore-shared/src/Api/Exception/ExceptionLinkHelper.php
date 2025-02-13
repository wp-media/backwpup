<?php

declare(strict_types=1);

/*
 * This file is part of the Inpsyde BackWpUp package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Restore\Api\Exception;

/**
 * Class ExceptionLinkHelper.
 *
 * @internal
 */
class ExceptionLinkHelper
{
    public static function translateWithAppropiatedLink(
        string $message,
        string $message_links
    ): string {
        $links_for_messages = self::links_for_messages();

        if (!isset($links_for_messages[$message_links])) {
            return $message;
        }

        return $message
            . ' ' . __('See the', 'backwpup') . ' '
            . self::link_markup(
                $links_for_messages[$message_links],
                __('documentation', 'backwpup')
            );
    }

    private static function link_markup(string $link, string $label): string
    {
        return '<a href="' . htmlentities($link) . '" ' .
            'target="_blank" rel="noopener noreferer">' .
            $label . '</a>';
    }

    /**
     * @return array<string>
     */
    private static function links_for_messages(): array
    {

        $message_links = [
            'DIR_CANNOT_BE_CREATED' => _x(
                'https://backwpup.com/docs/restore-directory-cannot-be-created/',
                'Link to documentation page for directory cannot be crated',
                'backwpup'
            ),
            'ARCHIVE_RESTORE_PATH_CANNOT_BE_SET' => _x(
                'https://backwpup.com/docs/archive-path-restore-path-not-set/',
                'Link to documentation page for archive restore path cannot be set',
                'backwpup'
            ),
            'DATABASE_CONNECTION_PROBLEMS' => _x(
                'https://backwpup.com/docs/restore-cannot-connect-mysql-database/',
                'Link to documentation page for database connection problems',
                'backwpup'
            ),
            'BZIP2_CANNOT_BE_DECOMPRESSED' => _x(
                'https://backwpup.com/docs/convert-bzip2-file-zip/',
                'Link to documentation page for bzip2 cannot be decompressed',
                'backwpup'
            ),
        ];

        return $message_links;
    }
}
