<?php

/**
 * Fix paths so they don't trigger an error on Windows.
 *
 * This class is meant to be a workaround for PHP bug #43817.
 *
 * On Windows IIS, if the parent directory is not readable, then the given directory will give access denied.
 *
 * @since 3.4.0
 */
class BackWPup_Path_Fixer
{
    /**
     * Fix the path if necessary.
     *
     * @param string $path
     *
     * @return string the fixed path
     */
    public static function fix_path($path)
    {
        if (get_site_option('backwpup_cfg_windows')) {
            $path = trailingslashit($path);

            if (is_dir($path . 'wp-content')) {
                return $path . 'wp-content/..';
            }
        }

        return $path;
    }

    public static function slashify($path)
    {
        return str_replace('\\', '/', $path);
    }
}
