<?php
/**
 * Unit tests for HTTP_Request2 package
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

// If running from SVN checkout, update include_path
if ('@' . 'package_version@' == '@package_version@') {
    $classPath   = realpath(dirname(dirname(__FILE__)));
    $includePath = array($classPath);

    foreach (explode(PATH_SEPARATOR, get_include_path()) as $component) {
        if (false !== ($real = realpath($component)) && $real !== $classPath) {
            $includePath[] = $real;
        }
    }

    set_include_path(implode(PATH_SEPARATOR, $includePath));
}

if (strpos($_SERVER['argv'][0], 'phpunit') === false) {
    /** Include PHPUnit dependencies based on version */
    require_once 'PHPUnit/Runner/Version.php';
    if (version_compare(PHPUnit_Runner_Version::id(), '3.5.0', '>=')) {
        require_once 'PHPUnit/Autoload.php';
    } else {
        require_once 'PHPUnit/Framework.php';
    }
}

if (!defined('HTTP_REQUEST2_TESTS_BASE_URL')
    && is_readable(dirname(__FILE__) . '/NetworkConfig.php')
) {
    require_once dirname(__FILE__) . '/NetworkConfig.php';
}
?>