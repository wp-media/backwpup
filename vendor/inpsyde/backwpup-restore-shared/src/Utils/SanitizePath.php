<?php

declare(strict_types=1);

/*
 * This file is part of the BackWPup Restore Shared package.
 *
 * (c) Guido Scialfa <dev@guidoscialfa.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Restore\Utils;

/**
 * @author Guido Scialfa <dev@guidoscialfa.com>
 */
class SanitizePath
{
    public const SLUG_SANITIZE_PATTERN = '/[^a-z0-9\-\_]*/';
    public const PATH_SANITIZE_PATTERN = '/[^a-zA-Z0-9\/\-\_\.]+/';

    /**
     * @param string $path The path to sanitize
     *
     * @return string The sanitized path
     */
    public static function sanitize(string $path): string
    {
        while (strpos($path, '..') !== false) {
            $path = str_replace('..', '', $path);
        }

        return ($path !== '/') ? $path : '';
    }

    /**
     * @param string $slug The slug to sanitize
     *
     * @return string The sanitize slug. May be empty.
     */
    public static function sanitizeSlugRegExp(string $slug): ?string
    {
        return preg_replace(static::SLUG_SANITIZE_PATTERN, '', $slug);
    }

    /**
     * Sanitize file path By RegExp.
     *
     * @param string $path The path to sanitize
     *
     * @return string The sanitized path
     */
    public static function sanitizeRegexp(string $path): string
    {
        // Sanitize template path and remove the path separator.
        // locate_template build the path in this way {STYLESHEET|TEMPLATE}PATH . '/' . $template_name.
        return self::sanitize(
            (string) preg_replace(static::PATH_SANITIZE_PATTERN, '', $path)
        );
    }
}
