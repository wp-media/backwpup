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

use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class ExceptionLinkHelper.
 *
 * @internal
 */
class ExceptionLinkHelper
{
    /**
     * @param TranslatorInterface&LocaleAwareInterface $translation
     */
    public static function translateWithAppropiatedLink(
        $translation,
        string $message,
        string $message_links
    ): string {
        $locale = self::region($translation);
        $links_for_messages = self::links_for_messages();

        if (!isset($links_for_messages[$message_links][$locale])) {
            return $message;
        }

        return $message
            . ' ' . $translation->trans('see the') . ' '
            . self::link_markup(
                $links_for_messages[$message_links][$locale],
                $translation->trans('documentation')
            );
    }

    private static function region(LocaleAwareInterface $translation): string
    {
        $locale = $translation->getLocale();
        $canonicalized_locale = str_replace('-', '_', $locale);
        $primary_languageIndex = strpos(
            $canonicalized_locale,
            '_'
        ) ?: \strlen($canonicalized_locale);

        return substr($canonicalized_locale, 0, $primary_languageIndex);
    }

    private static function link_markup(string $link, string $label): string
    {
        return '<a href="' . htmlentities($link) . '" ' .
            'target="_blank" rel="noopener noreferer">' .
            $label . '</a>';
    }

    /**
     * @return array<string, array<string, string>>
     */
    private static function links_for_messages(): array
    {
        static $message_links = null;

        if ($message_links === null) {
            $message_links = [
                'DIR_CANNOT_BE_CREATED' => [
                    'en' => 'https://backwpup.com/docs/restore-directory-cannot-be-created/',
                    'de' => 'https://backwpup.de/doku/restore-dekomprimierungsverzeichnis-kann-nicht-erstellt-werden/', // phpcs:ignore
                ],
                'ARCHIVE_RESTORE_PATH_CANNOT_BE_SET' => [
                    'en' => 'https://backwpup.com/docs/archive-path-restore-path-not-set/',
                    'de' => 'https://backwpup.de/doku/archivpfad-und-oder-restorepfad-ist-nicht-festgelegt/', // phpcs:ignore
                ],
                'DATABASE_CONNECTION_PROBLEMS' => [
                    'en' => 'https://backwpup.com/docs/restore-cannot-connect-mysql-database/',
                    'de' => 'https://backwpup.de/doku/verbindung-zur-mysql-datenbank-nicht-moeglich-1045-zugriff-verweigert-fuer-benutzer-localhost-mit-passwort-nein/', // phpcs:ignore
                ],
                'BZIP2_CANNOT_BE_DECOMPRESSED' => [
                    'en' => 'https://backwpup.com/docs/convert-bzip2-file-zip/',
                    'de' => 'https://backwpup.de/doku/bzip2-nach-zip-konvertieren/',
                ],
            ];
        }

        return $message_links;
    }
}
