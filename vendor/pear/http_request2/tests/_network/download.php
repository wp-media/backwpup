<?php
/**
 * Helper files for HTTP_Request2 unit tests. Should be accessible via HTTP.
 *
 * PHP version 5
 *
 * LICENSE
 *
 * This source file is subject to BSD 3-Clause License that is bundled
 * with this package in the file LICENSE and available at the URL
 * https://raw.github.com/pear/HTTP_Request2/trunk/docs/LICENSE
 *
 * @category  HTTP
 * @package   HTTP_Request2
 * @author    Alexey Borzov <avb@php.net>
 * @copyright 2008-2016 Alexey Borzov <avb@php.net>
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @link      http://pear.php.net/package/HTTP_Request2
 */

$payload = str_repeat('0123456789abcdef', 128);

if (array_key_exists('gzip', $_GET)) {
    // we inject a long "filename" into the header to check whether the downloader
    // can handle an incomplete header in "slowpoke" mode
    $payload = pack('c4Vc2', 0x1f, 0x8b, 8, 8, time(), 2, 255)
               . str_repeat('a_really_really_long_file_name', 10) . '.txt' . chr(0)
               . gzdeflate($payload)
               . pack('V2', crc32($payload), 2048);
    header('Content-Encoding: gzip');
}

if (!array_key_exists('slowpoke', $_GET)) {
    echo $payload;

} else {
    $pos    = 0;
    $length = strlen($payload);
    while ($pos < $length) {
        echo substr($payload, $pos, 65);
        $pos += 65;
        flush();
        usleep(50000);
    }
}