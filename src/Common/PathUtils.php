<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Common;

/**
 * @psalm-immutable
 */
final class PathUtils
{
    /**
     * @return list<non-empty-string>
     *
     * @psalm-pure
     */
    public static function split(string $path): array
    {
        $path = trim($path, '/');

        return array_values(array_filter(explode('/', $path)));
    }

    /**
     * @psalm-pure
     */
    public static function join(string ...$parts): string
    {
        // Strip all empty elements
        $parts = array_values(array_filter($parts));
        if (empty($parts)) {
            return '';
        }

        $isAbsolute = strpos($parts[0], '/') === 0;

        $path = '';

        foreach ($parts as $part) {
            $part = trim($part, '/');
            if (empty($part)) {
                continue;
            }

            if (!empty($path)) {
                $path .= '/';
            }

            $path .= $part;
        }

        return ($isAbsolute ? '/' : '') . $path;
    }
}
