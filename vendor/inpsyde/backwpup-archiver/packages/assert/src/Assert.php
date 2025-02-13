<?php # -*- coding: utf-8 -*-

/*
 * This file is part of the assert package.
 *
 * (c) Inpsyde GmbH <info@inpsyde.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Assert;

use Webmozart\Assert\Assert as WebMozartAssert;

/**
 * Class Assert
 *
 * @author Guido Scialfa <dev@guidoscialfa.com>
 */
class Assert extends WebMozartAssert
{
    /**
     * Assert Value Passed it's a String and a valid Local Path
     *
     * @param string $path
     * @param null $message
     */
    public static function path($path, $message = null)
    {
        // TODO Ensure string path format is correct

        parent::string($path);
        parent::notEmpty($path);
    }

    /**
     * Assert Given Value is Valid Url
     *
     * @param string $url
     * @param string $message
     */
    public static function url($url, $message = null)
    {
        $valid = (string)filter_var($url, FILTER_VALIDATE_URL) ?: false;

        if (!$valid || $valid !== $url) {
            static::reportInvalidArgument(
                sprintf(
                    $message ?: 'Expected a valid url. Got: %s',
                    static::typeToString($url)
                )
            );
        }
    }
}
