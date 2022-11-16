<?php
/**
 * Class BackWPup_Sanitize_Path.
 */
class BackWPup_Sanitize_Path
{
    /**
     * Slug Sanitizer Pattern.
     *
     * @var string The pattern for array keys
     */
    public const SLUG_SANITIZE_PATTERN = '/[^a-z0-9\-\_]*/';
    /**
     * Path Sanitizer Pattern.
     *
     * @var string The pattern to sanitize the paths
     */
    public const PATH_SANITIZE_PATTERN = '/[^a-zA-Z0-9\/\-\_\.]+/';

    /**
     * Sanitize path.
     *
     * @param string $path the path to sanitize
     *
     * @return string the sanitized path
     */
    public static function sanitize_path($path)
    {
        while (false !== strpos($path, '..')) {
            $path = str_replace('..', '', $path);
        }

        return ('/' !== $path) ? $path : '';
    }

    /**
     * Sanitize Slug By RegExp.
     *
     * @param string $slug the slug to sanitize
     *
     * @return string The sanitize slug. May be empty.
     */
    public static function sanitize_slug_reg_exp($slug)
    {
        return preg_replace(static::SLUG_SANITIZE_PATTERN, '', $slug);
    }

    /**
     * Sanitize file path By RegExp.
     *
     * @param string $path the path to sanitize
     *
     * @return string The sanitized path
     */
    public static function sanitize_path_regexp($path)
    {
        // Sanitize template path and remove the path separator.
        // locate_template build the path in this way {STYLESHEET|TEMPLATE}PATH . '/' . $template_name.
        return self::sanitize_path(
            preg_replace(static::PATH_SANITIZE_PATTERN, '', $path)
        );
    }
}
